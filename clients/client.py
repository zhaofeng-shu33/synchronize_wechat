'''
   A simple Python client to interact with wordpress backend,
   currently implementing login(general function) and ajax post.
'''
import yaml
import requests
import pdb
with open('config.yaml') as f:
    json = yaml.load(f.read())
    URL_ROOT = json['root']
    USER_NAME = json['username']
    USER_PASS = json['userpass']
    
URL_LOGIN = URL_ROOT + '/wp-login.php'
SYNC_WECHAT_URL = URL_ROOT + '/wp-admin/admin-ajax.php'
def login():
    s = requests.Session()
    payload = {'log':USER_NAME, 'pwd':USER_PASS}
    # corresponding to function `wp_signon` defined in `wp-includes/user.php`
    r = s.post(URL_LOGIN, data=payload, allow_redirects=False) # post form data
    if(r.status_code == 302):
        return s
    else:
        # response is html form, to be done, track the error message?
        pdb.set_trace()
        return False
def sync_wechat_ajax(session_object, dic):
    # tranditional form post
    r = session_object.post(SYNC_WECHAT_URL, data=dic)
    if(r.status_code == 200):
        return r.text
    else:
        return False

