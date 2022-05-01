//Javascript from Sbblog - Anderson Arruda < andmarruda@gmail.com >
//https://sysborg.com.br

const json2Line = (j, cols, id) => {
    let str = '';
    for(i of j){
        str += '<tr data-val-id="'+ i[id]+'">';
        for(col of cols){
            str += '<td data-val="'+ i[col] +'">'+ i[col] +'</td>';
        }
        str += '</tr>';
    }

    return str;
};

const searchToTable = async (form_id, table_id, url_fetch, url_logout, cols, id) => {
    let v = document.getElementById(form_id);
    let fd = new FormData(v);

    let f = await fetch(url_fetch, {
        method: 'POST',
        body: fd
    });

    if(!f.ok)
        location.href = url_logout;

    try{
        let j = await f.json();
        document.getElementById(table_id).getElementsByTagName('TBODY')[0].innerHTML = json2Line(j, cols, id);
    } catch(e){
        location.href = url_logout;
    }
};

const loadForm = (url, event) => {
    let tr = event.target.tagName.toUpperCase() == 'TR' ? event.target : event.target.closest('TR');

    if(tr.closest('THEAD') != null || tr.closest('TFOOT') != null)
        return;

    location.href = url + '/' + tr.getAttribute('data-val-id');
};

const getColumn = (arr, columnName) => {
    return arr.map((item => item[columnName]));
};