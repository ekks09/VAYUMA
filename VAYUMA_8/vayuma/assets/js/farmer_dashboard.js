function openSidebar() {

    const sidebar =
        document.getElementById("farmerSidebar");

    const overlay =
        document.getElementById("sidebarOverlay");

    sidebar.classList.add("open");

    overlay.classList.add("active");
}


function closeSidebar() {

    const sidebar =
        document.getElementById("farmerSidebar");

    const overlay =
        document.getElementById("sidebarOverlay");

    sidebar.classList.remove("open");

    overlay.classList.remove("active");
}


/*
|--------------------------------------------------------------------------
| Close sidebar after selecting a link on mobile
|--------------------------------------------------------------------------
*/

document.querySelectorAll(".nav-item").forEach(function(item) {

    item.addEventListener("click", function() {

        if (window.innerWidth <= 800) {

            closeSidebar();

        }

    });

});