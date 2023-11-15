//Javascript from Sbblog - Anderson Arruda < andmarruda@gmail.com >
//https://sysborg.com.br

const clientSideInfos = () => {
    return {
        timeZoneGMT: -(new Date()).getTimezoneOffset() / 60,
        referrer: document.referrer,
        numberPrevSites: history.length,
        browserUserAgent: window.navigator.userAgent,
        browserLanguage: window.navigator.language,
        browserOnline: window.navigator.onLine,
        cookiesEnabled: window.navigator.cookieEnabled,
        screenWidth: screen.width,
        screenHeight: screen.height,
        /*documentWidth: document.width,
        documentHeight: document.height,
        documentInWidth: document.innerWidth,
        documentInHeight: document.innerHeight,
        windowInWidth: window.innerWidth,
        windowInHeight: window.innerHeight,
        availWidth: screen.availWidth,
        availHeight: screen.availHeight,*/
        colorDepth: screen.colorDepth,
        pixelDepth: screen.pixelDepth,
        hardwareConcurrency: window.navigator.hardwareConcurrency,
        language: window.navigator.language,
        languages: window.navigator.languages,
        buildId: window.navigator.buildID,
        clipboard: window.navigator.clipboard,
        mediaSession: window.navigator.mediaSession,
        pdfViewerEnabled: window.navigator.pdfViewerEnabled,
        vendor: window.navigator.vendor,
        webdriver: window.navigator.webdriver
    };
};

let visitObj;
const registerVisit = async (url, article_id, csrf_token) => {
    let fd = new FormData();
    fd.append('user_details', JSON.stringify(clientSideInfos()));
    fd.append('article_id', article_id);
    
    let header = new Headers();
    header.append('X-CSRF-Token', csrf_token);

    let f = await fetch(url, {
        method: 'post',
        headers: header,
        body: fd
    });

    visitObj = await f.json();
    Object.freeze(visitObj);
};

const registerExit = (url, csrf_token) => {
    if(!('id' in visitObj) || !('hash' in visitObj))
        return;

    const fd = new FormData();
    fd.append('id', visitObj.id);
    fd.append('hash', visitObj.hash);
    fd.append('_token', csrf_token);

    window.navigator.sendBeacon(url, fd);
};