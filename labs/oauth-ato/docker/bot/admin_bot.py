import time
import requests
from selenium import webdriver
from selenium.common.exceptions import WebDriverException

BASE   = "http://nginx"
NEXT   = f"{BASE}/next-report"
SECRET = f"{BASE}/secret"


def wait_for_selenium():
    url = "http://selenium:4444/wd/hub/status"
    for _ in range(30):
        try:
            r = requests.get(url, timeout=2)
            if r.status_code == 200:
                print("Selenium is ready")
                return
            else:
                print("Selenium not ready yet, status:", r.status_code)
        except Exception as e:
            print("Selenium wait error:", e)
        time.sleep(2)
    raise RuntimeError("Selenium did not become ready in time")


def create_driver():
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

    # Log in as admin before visiting reported URLs
    driver.get(SECRET)
    time.sleep(1)

    return driver


def main():
    wait_for_selenium()

    http = requests.Session()
    driver = create_driver()

    while True:
        try:
            resp = http.get(NEXT, timeout=5)
            url = resp.text.strip()

            if not url:
                time.sleep(15)
                continue

            print("Bot visiting raw:", repr(url))
            full = BASE + url
            print("Full URL:", full)

            try:
                driver.get(full)

            except WebDriverException as e:
                msg = str(e)
                if "Unable to find session" in msg:
                    print("WebDriver session lost, recreating...")
                    try:
                        driver.quit()
                    except Exception:
                        pass
                    driver = create_driver()
                    driver.get(full)
                else:
                    print("WebDriver error:", e)

            time.sleep(5)

        except Exception as e:
            print("Bot error:", e)
            time.sleep(10)


if __name__ == "__main__":
    main()
