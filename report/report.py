import requests

def send_report(status):
    url            = 'http://vtm.pp.ua/projects/archlinux/upload_logs.php'
    site_available = False

    try:
        response       = requests.head(url)
        site_available = (response.status_code == 200)
    except requests.RequestException as e:
        pass

    if status not in ['success', 'error']:
        return

    if site_available and status in ['success', 'error']:
        data = {'key': 'KgwGdGamVq9ak4f6xzZw', 'status': status}

        try:
            with open('installer.log', 'rb') as file:
                files = {'file': file}
                requests.post(url, files=files, data=data)
        except FileNotFoundError:
            pass
        except Exception as e:
            pass
