#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from selenium import webdriver
from selenium.webdriver.firefox.options import Options
from selenium.webdriver.firefox.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from bs4 import BeautifulSoup
import time
import json
from datetime import datetime

def parse_with_firefox(url):
    print("🦊 Запускаем Firefox...")
    
    firefox_options = Options()
    firefox_options.add_argument('--headless')
    firefox_options.add_argument('--no-sandbox')
    
    try:
        # Пробуем стандартный Firefox
        driver = webdriver.Firefox(options=firefox_options)
    except Exception as e:
        print(f"❌ Firefox не запустился: {e}")
        print("\n💡 Альтернатива: используем GeckoDriver через webdriver-manager")
        try:
            from webdriver_manager.firefox import GeckoDriverManager
            service = Service(GeckoDriverManager().install())
            driver = webdriver.Firefox(service=service, options=firefox_options)
        except Exception as e2:
            print(f"❌ И это не сработало: {e2}")
            return None
    
    try:
        print(f"📥 Загружаем {url}...")
        driver.get(url)
        
        # Ждем загрузки
        wait = WebDriverWait(driver, 15)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, 'h1.font__headline5')))
        time.sleep(2)
        
        # Парсим
        html = driver.page_source
        soup = BeautifulSoup(html, 'html.parser')
        
        title = soup.select_one('h1.font__headline5')
        article = soup.select_one('.help-page__item_text')
        
        result = {
            'url': url,
            'title': title.get_text(strip=True) if title else None,
            'timestamp': datetime.now().isoformat(),
            'content': article.get_text(separator='\n', strip=True) if article else None,
            'success': True
        }
        
        # Сохраняем
        filename = f"output/firefox_result_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json"
        with open(filename, 'w', encoding='utf-8') as f:
            json.dump(result, f, ensure_ascii=False, indent=2)
        
        print(f"✅ Успешно! Заголовок: {result['title']}")
        print(f"💾 Сохранено в {filename}")
        
        driver.quit()
        return result
        
    except Exception as e:
        print(f"❌ Ошибка: {e}")
        if 'driver' in locals():
            driver.quit()
        return None

if __name__ == "__main__":
    parse_with_firefox("https://armtek.ru/help/legal-information/oferta")