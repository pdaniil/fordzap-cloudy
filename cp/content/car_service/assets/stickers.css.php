<style>
    .sticker-form {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }
    .sticker-form input[type="text"], .sticker-form input[type="color"] {
        padding: 5px;
        font-size: 14px;
    }
    .sticker-form button {
        padding: 6px 12px;
        background: #28a745;
        color: white;
        border: none;
        cursor: pointer;
    }
    .sticker-form button:hover {
        background: #218838;
    }

    table.stickers-table {
        width: 100%;
        border-collapse: collapse;
    }
    table.stickers-table th, table.stickers-table td {
        border: 1px solid #ccc;
        padding: 8px;
        text-align: left;
    }
    table.stickers-table td input[type="text"], 
    table.stickers-table td input[type="color"] {
        width: 100%;
        border: none;
        padding: 4px;
        font-size: 14px;
    }
    table.stickers-table td input:focus {
        outline: 1px solid #007bff;
    }
    .delete-btn {
        padding: 4px 8px;
        background: #dc3545;
        color: white;
        border: none;
        cursor: pointer;
    }
    .delete-btn:hover {
        background: #c82333;
    }
</style>