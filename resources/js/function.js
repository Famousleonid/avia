window.test = function () {
    console.log('глобально js функция');
}

window.encrypt = function (str) {

    if (isString(str)) {
        let encrypted = CryptoJS.AES.encrypt(str, "mdxspl");
        return encrypted.toString();
    }
    return 'на шифрование передана НЕ строка';
}
window.decrypt = function (str) {

    // if(str === null) str = "U2FsdGVkX1+HiAO3zYyy7PZloLethKzcdU+9J+2ztPo=";

    if (isString(str)) {
        let decrypted = CryptoJS.AES.decrypt(str, "mdxspl");
        return decrypted.toString(CryptoJS.enc.Utf8);
    }
    return 'nonStr';
}

window.isString = function (val) {
    return (typeof val === "string" || val instanceof String);
}

window.IsJsonString = function (str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
