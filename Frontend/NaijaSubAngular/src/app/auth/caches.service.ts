export abstract class CacheService {
  protected getItem<T>(key: string): T {
    const data = localStorage.getItem(key);
    if (data && data !== 'undefined') {
      return JSON.parse(data);
    }
    return null;
  }

  protected setItem(key: string, data: object | string) {
    if (typeof data === 'string') {
      localStorage.setItem(key, data);
    }
    localStorage.setItem(key, JSON.stringify(data));
  }

  protected removeItem(key: string) {
    localStorage.removeItem(key);
  }

  protected clear() {
    localStorage.clear();
  }

  public setCookie(cname, cvalue, exdays) {
    const d = new Date();
    d.setTime(d.getTime() + exdays * 24 * 60 * 60 * 1000);
    const expires = 'expires=' + d.toUTCString();
    document.cookie = cname + '=' + cvalue + ';' + expires + ';path=/';
  }

  public getCookie( cname ) {
    const name = cname + '=';
    const ca = document.cookie.split(';');
    for ( let i = 0; i < ca.length; i++ ) {
      let c = ca[i];
      while (c.charAt(0) == ' ') {
        c = c.substring(1);
      }
      if (c.indexOf(name) == 0) {
        return c.substring(name.length, c.length);
      }
    }
    return '';
  }

   public deleteCookie(cname, cvalue, exdays) {
    const d = new Date();
    d.setTime(d.getTime() - exdays * 24 * 60 * 60 * 1000);
    const expires = 'expires=' + d.toUTCString();
    document.cookie = cname + '=' + cvalue + ';' + expires + ';path=/';
  }

}
