#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import sys
import subprocess

print("🔍 Диагностика Firefox на сервере")
print("="*50)

# Проверяем версию Python
print(f"🐍 Python: {sys.version}")

# Проверяем установленные пакеты
print("\n📦 Установленные пакеты:")
subprocess.run([sys.executable, "-m", "pip", "list"])

# Проверяем наличие Firefox
print("\n🦊 Поиск Firefox:")
for cmd in ["which firefox", "which firefox-esr", "ls -la /usr/bin/firefox*"]:
    print(f"\n$ {cmd}")
    subprocess.run(cmd, shell=True)

# Проверяем наличие библиотек
print("\n📚 Проверка библиотек:")
subprocess.run("ldconfig -p | grep -E 'nss|nspr|glib' | head -20", shell=True)

print("\n" + "="*50)
print("🚀 Запускаем тест Selenium...")

from selenium import webdriver
from selenium.webdriver.firefox.options import Options

try:
    options = Options()
    options.add_argument('--headless')
    
    print("Пробуем запустить Firefox...")
    driver = webdriver.Firefox(options=options)
    print("✅ Firefox запущен!")
    
    driver.get("https://example.com")
    print(f"✅ Заголовок страницы: {driver.title}")
    
    driver.quit()
    print("✅ Тест пройден!")
    
except Exception as e:
    print(f"❌ Ошибка: {e}")
    
    # Пробуем альтернативный способ
    print("\n🔄 Пробуем с webdriver-manager...")
    try:
        from selenium.webdriver.firefox.service import Service
        from webdriver_manager.firefox import GeckoDriverManager
        
        service = Service(GeckoDriverManager().install())
        driver = webdriver.Firefox(service=service, options=options)
        print("✅ Firefox запущен через webdriver-manager!")
        
        driver.quit()
    except Exception as e2:
        print(f"❌ И это не сработало: {e2}")