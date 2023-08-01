document.addEventListener("DOMContentLoaded", function () {
    const modeSwitch = document.querySelector(".mode-switch");
    const listView = document.querySelector(".list-view");
    const gridView = document.querySelector(".grid-view");
    const buildingsList = document.querySelector(".building-boxes");

    // default settings
    let userSettings = {
        darkMode: false,
        viewMode: "gridView",
    };

    // read settings from cookie
    const readSettingsFromCookie = () => {
        const settingsCookie = document.cookie.replace(
            /(?:(?:^|.*;\s*)userSettings\s*\=\s*([^;]*).*$)|^.*$/,
            "$1"
        );

        if (settingsCookie) {
            userSettings = JSON.parse(decodeURIComponent(settingsCookie));
        } else {
            //   create cookie with default settings
            document.cookie = `userSettings=${encodeURIComponent(
                JSON.stringify(userSettings)
            )}`;
        }
    };

    // save settings to cookie
    const saveSettingsToCookie = () => {
        document.cookie = `userSettings=${encodeURIComponent(
            JSON.stringify(userSettings)
        )}`;
    };

    // apply settings
    const applySettings = () => {
        //  theme mode
        if (userSettings.darkMode) {
            document.documentElement.classList.add("dark");
        } else {
            document.documentElement.classList.remove("dark");
        }

        // view mode
        gridView.classList.remove("active");
        listView.classList.remove("active");
        buildingsList.classList.remove("jsListView");
        buildingsList.classList.remove("jsGridView");

        if (userSettings.viewMode === "gridView") {
            gridView.classList.add("active");
            buildingsList.classList.add("jsGridView");
        } else if (userSettings.viewMode === "listView") {
            listView.classList.add("active");
            buildingsList.classList.add("jsListView");
        }
    };

    //   change settings
    const changeSetting = (setting, value) => {
        userSettings[setting] = value;
        saveSettingsToCookie();
        applySettings();
    };

    // read settings from cookie
    readSettingsFromCookie();
    applySettings();

    // change thems
    modeSwitch.addEventListener("click", function () {
        changeSetting("darkMode", !userSettings.darkMode);
        // modeSwitch.classList.toggle("active");
    });

    // change view
    listView.addEventListener("click", function () {
        changeSetting("viewMode", "listView");
    });

    gridView.addEventListener("click", function () {
        changeSetting("viewMode", "gridView");
    });

    document
        .querySelector(".register-btn")
        .addEventListener("click", function () {
            document.querySelector(".register-section").classList.add("show");
        });

    document
        .querySelector(".register-close")
        .addEventListener("click", function () {
            document
                .querySelector(".register-section")
                .classList.remove("show");
        });
});

//  alert
function closeAlert() {
    const alertElement = document.getElementById("myAlert");
    alertElement.style.display = "none";
}

const alertMessage = document.getElementById("myAlert");
if (alertMessage) setTimeout(closeAlert, 3000);

function activateElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.add("active");
    }
}

function deactivateElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.remove("active");
    }
}

function showHideWindows(windowClass) {
    const windows = document.querySelectorAll(`.${windowClass}`);
    windows.forEach((window) => {
        window.style.display = "block";
    });
}

function hideWindows(windowClass) {
    const windows = document.querySelectorAll(`.${windowClass}`);
    windows.forEach((window) => {
        window.style.display = "none";
    });
}

function handleClick(windowClass) {
    const displayedWindows = [
        "home-window",
        "setting-window",
        "register-window",
    ];
    const hiddenWindows = ["home", "setting", "register"];
    const registerBtn = document.querySelector(".register-btn");

    displayedWindows.forEach((window, index) => {
        if (window === windowClass) {
            showHideWindows(window);
            activateElement(hiddenWindows[index]);
        } else {
            hideWindows(window);
            deactivateElement(hiddenWindows[index]);
        }
    });
}

// more option for show edit unit
const moreWrappers = document.querySelectorAll(".more-wrapper");
const views = document.querySelector(".building-boxes");

moreWrappers.forEach((moreWrapper) => {
    moreWrapper.addEventListener("click", function (event) {
        const dropdownDot = event.currentTarget.querySelector(".dropdownDots");
        if (dropdownDot && dropdownDot.classList.contains("dropdownDots")) {
            if (views && views.classList.contains("jsGridView")) {
                dropdownDot.classList.toggle("hidden");
                dropdownDot.classList.toggle("right-7");
            } else if (views && views.classList.contains("jsListView")) {
                dropdownDot.classList.toggle("hidden");
                dropdownDot.classList.toggle("left-7");
            }
        }
    });
});

//  modal
const modal = document.querySelector(".modal");
const openBtns = document.querySelectorAll(".open-Btn");
const closeBtn = document.querySelector(".close-Btn");
const complexId = document.querySelector("#complexId");

openBtns.forEach((openBtn) => {
    openBtn.addEventListener("click", (event) => {
        modal.classList.remove("hidden");
        complexId.value = event.target.closest(
            ".building-box-header"
        ).nextElementSibling.children[0].textContent;
    });
});

closeBtn.addEventListener("click", () => {
    modal.classList.add("hidden");
});

//  add unit modal
const addUnitModal = document.querySelector(".add-unit-modal");
const openAddUnitBtns = document.querySelectorAll(".open-add-unit-Btn");
const closeAddUnitBtn = document.querySelector(".close-add-unit-Btn");
const addUnitComplexId = document.querySelector("#addUnitComplexId");

openAddUnitBtns.forEach((openAddUnitBtns) => {
    openAddUnitBtns.addEventListener("click", (event) => {
        addUnitModal.classList.remove("hidden");
        addUnitComplexId.value = event.target.closest(
            ".building-box-header"
        ).nextElementSibling.children[0].textContent;
    });
});

closeAddUnitBtn.addEventListener("click", () => {
    addUnitModal.classList.add("hidden");
});
