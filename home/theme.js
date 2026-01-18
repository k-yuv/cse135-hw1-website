LIGHT = "light";
DARK = "dark";

LIGHT_ICON = "🤍";   
DARK_ICON = "🖤";  

function setTheme(theme) {
    const root = document.documentElement;
    const toggleBtn = document.getElementById("theme-toggle");
    
    if (theme === DARK) {
        root.setAttribute("data-theme", DARK);
        toggleBtn.innerText = DARK_ICON;
    } 
    else {
        root.setAttribute("data-theme", LIGHT);
        toggleBtn.innerText = LIGHT_ICON;
    }
}

function toggleTheme() {
    const currTheme = localStorage.getItem("theme") || LIGHT;
    const newTheme = (currTheme === LIGHT) ? DARK : LIGHT;
    localStorage.setItem("theme", newTheme);
    setTheme(newTheme);
}

function setSavedTheme() {
    const savedTheme = localStorage.getItem("theme") || LIGHT;
    setTheme(savedTheme);

    const toggleBtn = document.getElementById("theme-toggle");
    if (savedTheme === DARK) {
        toggleBtn.innerText = DARK_ICON;
    } else {
        toggleBtn.innerText = LIGHT_ICON;
    }
}

document.addEventListener("DOMContentLoaded", () => {
    setSavedTheme();
    const toggleBtn = document.getElementById("theme-toggle");
    toggleBtn.addEventListener("click", toggleTheme);
});