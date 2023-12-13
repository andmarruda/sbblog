const deleteArticle = (url) => {

};

const modal_id = 'confirm_modal_global';
const confirmModal = (title, body_delete, body_restore, cancel_label, confirm_label, deletable, callback) => {
    const html = `<div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">${title}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>${deletable ? body_delete : body_restore}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">${cancel_label}</button>
                <button type="button" class="btn btn-outline-danger" id="action_confirm_modal">${confirm_label}</button>
            </div>
        </div>
    </div>`;

    const div = document.createElement('div');
    div.className = 'modal fade';
    div.setAttribute('tabindex', '-1');
    div.setAttribute('id', modal_id);
    div.innerHTML = html;

    div.querySelector('#action_confirm_modal').addEventListener('click', () => {
        callback();
    });

    document.body.appendChild(div);

    const modal = new bootstrap.Modal(document.getElementById(modal_id));
    modal.show();
    document.getElementById(modal_id).addEventListener('hidden.bs.modal', () => {
        document.getElementById(modal_id).remove();
    });
};