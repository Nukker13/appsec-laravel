import time, json, urllib.request
from selenium import webdriver

LAB_BASE     = "http://nginx"
SELENIUM_URL = "http://selenium:4444"   # без /wd/hub
INTERVAL     = 15

def wait_selenium(url, timeout=60):
    deadline = time.time() + timeout
    status_url = f"{url}/status"
    while time.time() < deadline:
        try:
            with urllib.request.urlopen(status_url, timeout=3) as resp:
                data = json.load(resp)
                if data.get("value", {}).get("ready"):
                    print("Selenium is ready")
                    return
        except Exception:
            pass
        print("Waiting for Selenium...")
        time.sleep(2)
    raise RuntimeError("Selenium did not become ready in time")

def main():
    wait_selenium(SELENIUM_URL)

    options = webdriver.ChromeOptions()
    options.add_argument("--headless=new")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")

    driver = webdriver.Remote(command_executor=SELENIUM_URL, options=options)
    try:
        print("Visiting /secret …")
        driver.get(f"{LAB_BASE}/secret")
        time.sleep(1)

        print("Watching /admin …")
        driver.get(f"{LAB_BASE}/admin")
        while True:
            time.sleep(INTERVAL)
            print("Reload /admin …")
            driver.refresh()
    finally:
        driver.quit()

if __name__ == "__main__":
    main()