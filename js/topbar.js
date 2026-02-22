function toggleDropdown(){
    const menu = document.getElementById("dropdownMenu");
    if(menu) menu.classList.toggle("show");
}
window.onclick = function(event){
    if(!event.target.closest('.user-container')){
        const menu = document.getElementById("dropdownMenu");
        if(menu) menu.classList.remove('show');
    }
}
