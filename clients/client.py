'''
   A simple Python client to interact with wordpress backend,
   currently implementing login(general function) and ajax post for 
'''
URL_ROOT = ''
URL_LOGIN = ''
SYNC_WECHAT_URL = URL_ROOT + '/wp-admin/admin-ajax.php'
import requests
def login(username, userpass):
    s = requests.Session()
    return s
def sync_wechat_ajax():
    return
