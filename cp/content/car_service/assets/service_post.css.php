<style>
.icon_wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    width: fit-content;
}
.post_image {
    width: 200px;
    height: 200px;
    cursor: pointer;
}
.icons_list {
    width: 229px;
    height: 250px;
    overflow-y: auto;
    overflow-x: hidden;
}
.icon {
    width: 50px;
    height: 50px;
    cursor: pointer;
}
.icon:hover {
    transform: scale(1.1);
}
.close_button {
    position: sticky;
    height: 40px;
    width: 100%;
    bottom: 0;
    left: 0;
    z-index: 99999;
    background: #3b3b3b;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
}
.close_image img {
    width: 30px;
    height: 30px;
}
</style>