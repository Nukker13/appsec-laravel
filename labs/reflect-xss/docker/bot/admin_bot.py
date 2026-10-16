import time
import requests
from selenium import webdriver

BASE   = "http://nginx"          # хост приложения внутри docker-сети
NEXT   = f"{BASE}/next-report"
SECRET = f"{BASE}/secret"        # роут, который выдаёт админскую сессию


def wait_for_selenium():
    url = "http://selenium:4444/wd/hub/status"
    for _ in range(30):  # до ~60 секунд ожидания
        try:
            r = requests.get(url, timeout=2)
            if r.status_code == 200:
                print("Selenium is ready")
                return
        except Exception:
            pass
        time.sleep(2)
    raise RuntimeError("Selenium did not become ready in time")


def main():
    wait_for_selenium()

    options = webdriver.ChromeOptions()
    options.add_argument("--headless=new")
    options.add_argument("--disable-gpu")
    options.add_argument("--no-sandbox")
    options.add_argument("--log-level=3")
    options.add_experimental_option("excludeSwitches", ["enable-logging"])

    driver = webdriver.Remote(
        command_executor="http://selenium:4444/wd/hub",
        options=options,
    )

    # 1) Получаем админскую сессию прямо в браузере
    driver.get(SECRET)
    time.sleep(1)  # даём PHP выставить cookies

    # 2) HTTP-сессия только для чтения /next-report
    http = requests.Session()

    while True:
        try:
            resp = http.get(NEXT, timeout=5)
            url = resp.text.strip()

            if not url:
                time.sleep(15)
                continue

            print("Bot visiting:", url)
            driver.get(BASE + url)

            # даём XSS время исполниться
            time.sleep(5)

        except Exception as e:
            print("Bot error:", e)
            time.sleep(10)


if __name__ == "__main__":
    main()