<script>
document.addEventListener("DOMContentLoaded", loadStickers);

function loadStickers() {
    fetch(url_request + "?action=list")
        .then(r => r.json())
        .then(data => {
            let tbody = document.getElementById("stickersBody");
            tbody.innerHTML = "";
            data.forEach(sticker => {
                let tr = document.createElement("tr");

                // Текст
                let tdText = document.createElement("td");
                let inputText = document.createElement("input");
                inputText.type = "text";
                inputText.value = sticker.text;
                inputText.onchange = () => updateSticker(sticker.id, inputText.value, inputColor.value);
                tdText.appendChild(inputText);

                // Цвет
                let tdColor = document.createElement("td");
                let inputColor = document.createElement("input");
                inputColor.type = "color";
                inputColor.value = sticker.bg_color;
                inputColor.onchange = () => updateSticker(sticker.id, inputText.value, inputColor.value);
                tdColor.appendChild(inputColor);

                // Кнопка удаления
                let tdDelete = document.createElement("td");
                let btnDelete = document.createElement("button");
                btnDelete.textContent = "Удалить";
                btnDelete.className = "delete-btn";
                btnDelete.onclick = () => deleteSticker(sticker.id);
                tdDelete.appendChild(btnDelete);

                tr.appendChild(tdText);
                tr.appendChild(tdColor);
                tr.appendChild(tdDelete);
                tbody.appendChild(tr);
            });
        });
}

function addSticker() {
    let text = document.getElementById("newText").value.trim();
    let color = document.getElementById("newColor").value;
    if (!text) return alert("Введите текст стикера");

    fetch(url_request + "?action=add", {
        method: "POST",
        body: new URLSearchParams({ text, color })
    }).then(() => {
        document.getElementById("newText").value = "";
        loadStickers();
    });
}

function updateSticker(id, text, color) {
    fetch(url_request + "?action=update", {
        method: "POST",
        body: new URLSearchParams({ id, text, color })
    });
}

function deleteSticker(id) {
    if (!confirm("Удалить этот стикер?")) return;
    fetch(url_request + "?action=delete", {
        method: "POST",
        body: new URLSearchParams({ id })
    }).then(() => loadStickers());
}

</script>