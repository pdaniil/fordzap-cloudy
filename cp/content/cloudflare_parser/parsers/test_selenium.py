#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""Простой тест для проверки работы Selenium"""

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
import time

def test_selenium():
    print("🔄 Тестируем Selenium...")
    
    options = Options()
    options.add_argument('--headless')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    
    try:
        from webdriver_manager.chrome import ChromeDriverManager
        from selenium.webdriver.chrome.service import Service
        
        service = Service(ChromeDriverManager().install())
        driver = webdriver.Chrome(service=service, options=options)
        
        print("✅ Драйвер запущен")
        
        # Тест загрузки страницы
        driver.get('https://example.com')
        print(f"✅ Страница загружена, заголовок: {driver.title}")
        
        driver.quit()
        print("✅ Тест пройден успешно!")
        return True
        
    except Exception as e:
        print(f"❌ Ошибка: {e}")
        return False

if __name__ == "__main__":
    test_selenium()