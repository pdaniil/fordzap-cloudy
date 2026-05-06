#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Парсер для сайта armtek.ru
Сохраняет содержимое страницы оферты
"""

import os
import sys
import json
import logging
from datetime import datetime
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from bs4 import BeautifulSoup
import time

# Настройка логирования
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('logs/parser.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

class ArmtekParser:
    def __init__(self, headless=True):
        self.headless = headless
        self.driver = None
        self.setup_driver()
    
    def setup_driver(self):
        """Настройка драйвера Chrome"""
        chrome_options = Options()
        
        if self.headless:
            chrome_options.add_argument('--headless')
        
        # Важные опции для работы на сервере
        chrome_options.add_argument('--no-sandbox')
        chrome_options.add_argument('--disable-dev-shm-usage')
        chrome_options.add_argument('--disable-gpu')
        chrome_options.add_argument('--disable-software-rasterizer')
        chrome_options.add_argument('--disable-setuid-sandbox')
        chrome_options.add_argument('--window-size=1920,1080')
        
        # User-Agent
        chrome_options.add_argument('--user-agent=Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
        
        try:
            from webdriver_manager.chrome import ChromeDriverManager
            from selenium.webdriver.chrome.service import Service
            
            service = Service(ChromeDriverManager().install())
            self.driver = webdriver.Chrome(service=service, options=chrome_options)
            logger.info("Драйвер Chrome успешно запущен")
        except Exception as e:
            logger.error(f"Ошибка запуска драйвера: {e}")
            sys.exit(1)
    
    def parse_offer_page(self, url):
        """Парсинг страницы оферты"""
        try:
            logger.info(f"Загрузка страницы: {url}")
            self.driver.get(url)
            
            # Ждем загрузки заголовка
            wait = WebDriverWait(self.driver, 15)
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, 'h1.font__headline5')))
            
            # Даем время на выполнение JavaScript
            time.sleep(3)
            
            # Получаем HTML
            html = self.driver.page_source
            
            # Парсим с BeautifulSoup
            soup = BeautifulSoup(html, 'html.parser')
            
            # Извлекаем данные
            title = soup.select_one('h1.font__headline5')
            article = soup.select_one('.help-page__item_text')
            
            # Извлекаем все заголовки из статьи
            headers = []
            if article:
                headers = [h.get_text(strip=True) for h in article.find_all(['h1', 'h2', 'h3'])]
            
            result = {
                'url': url,
                'title': title.get_text(strip=True) if title else None,
                'timestamp': datetime.now().isoformat(),
                'headers': headers,
                'content_preview': article.get_text(strip=True)[:500] + '...' if article else None,
                'full_content': article.get_text(separator='\n', strip=True) if article else None,
                'html': html
            }
            
            logger.info(f"Страница успешно загружена. Заголовок: {result['title']}")
            return result
            
        except Exception as e:
            logger.error(f"Ошибка при парсинге: {e}")
            return None
    
    def save_to_json(self, data, filename=None):
        """Сохранение данных в JSON файл"""
        if not filename:
            filename = f"output/armtek_offer_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json"
        
        try:
            with open(filename, 'w', encoding='utf-8') as f:
                json.dump(data, f, ensure_ascii=False, indent=2)
            logger.info(f"Данные сохранены в {filename}")
            return filename
        except Exception as e:
            logger.error(f"Ошибка сохранения JSON: {e}")
            return None
    
    def save_to_html(self, html_content, filename=None):
        """Сохранение HTML в файл"""
        if not filename:
            filename = f"output/armtek_offer_{datetime.now().strftime('%Y%m%d_%H%M%S')}.html"
        
        try:
            with open(filename, 'w', encoding='utf-8') as f:
                f.write(html_content)
            logger.info(f"HTML сохранен в {filename}")
            return filename
        except Exception as e:
            logger.error(f"Ошибка сохранения HTML: {e}")
            return None
    
    def save_to_text(self, text_content, filename=None):
        """Сохранение текста в файл"""
        if not filename:
            filename = f"output/armtek_offer_{datetime.now().strftime('%Y%m%d_%H%M%S')}.txt"
        
        try:
            with open(filename, 'w', encoding='utf-8') as f:
                f.write(text_content)
            logger.info(f"Текст сохранен в {filename}")
            return filename
        except Exception as e:
            logger.error(f"Ошибка сохранения текста: {e}")
            return None
    
    def close(self):
        """Закрытие драйвера"""
        if self.driver:
            self.driver.quit()
            logger.info("Драйвер закрыт")

def main():
    """Основная функция"""
    # URL для парсинга
    url = "https://armtek.ru/help/legal-information/oferta"
    
    # Создаем парсер
    parser = ArmtekParser(headless=True)
    
    try:
        # Парсим страницу
        data = parser.parse_offer_page(url)
        
        if data:
            # Сохраняем в разных форматах
            json_file = parser.save_to_json(data)
            html_file = parser.save_to_html(data['html'])
            text_file = parser.save_to_text(data['full_content'] if data['full_content'] else '')
            
            # Выводим информацию
            print("\n" + "="*50)
            print(f"✅ ПАРСИНГ УСПЕШНО ВЫПОЛНЕН")
            print("="*50)
            print(f"📄 Заголовок: {data['title']}")
            print(f"📁 JSON: {json_file}")
            print(f"📁 HTML: {html_file}")
            print(f"📁 TXT: {text_file}")
            print(f"📊 Разделы статьи:")
            for i, header in enumerate(data['headers'][:10], 1):  # Первые 10 заголовков
                print(f"   {i}. {header[:100]}")
            print("="*50)
        else:
            print("❌ Ошибка парсинга")
            
    finally:
        # Обязательно закрываем драйвер
        parser.close()

if __name__ == "__main__":
    main()