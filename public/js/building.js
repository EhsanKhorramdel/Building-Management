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
            const appSidebarLinkActive = document.querySelector(
                ".app-sidebar-link.active"
            ).id;
            const registerSections =
                document.querySelectorAll(".register-section");
            registerSections.forEach((registerSection) => {
                if (
                    registerSection.classList.contains(
                        `${appSidebarLinkActive}-window`
                    )
                )
                    registerSection.classList.add("show");
            });
        });

    const registerCloses = document.querySelectorAll(".register-close");
    registerCloses.forEach((registerClose) => {
        registerClose.addEventListener("click", () => {
            document
                .querySelector(".register-section.show")
                .classList.remove("show");
        });
    });
});

// more option for show Notification Picture
const modal = document.querySelector(".modal");

const openBtns = document.querySelectorAll(".open-Btn");
const closeBtn = document.querySelector(".close-Btn");
const notifPicture = document.querySelector("#NotifPicture");

openBtns.forEach((openBtn) => {
    openBtn.addEventListener("click", (event) => {
        modal.classList.remove("hidden");
        notifPicture.src = event.target.nextElementSibling.textContent.trim();
    });
});

closeBtn.addEventListener("click", () => {
    modal.classList.add("hidden");
});

//  alert
function closeAlert() {
    const alertElement = document.getElementById("myAlert");
    alertElement.style.display = "none";
}

const alertMessage = document.getElementById("myAlert");
if (alertMessage) setTimeout(closeAlert, 3000);

// click sidebar's icon (hide & show, active & deactive)
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
        "notification-window",
        "poll-window",
        "cost-window",
        "charge-window",
        "paymentInfo-window",
        "chat-window",
    ];
    const hiddenWindows = [
        "home",
        "setting",
        "notification",
        "poll",
        "cost",
        "charge",
        "paymentInfo",
        "chat",
    ];
    const registerBtn = document.querySelector(".register-btn");
    const isManager = document.querySelector(".isManager").textContent;

    displayedWindows.forEach((window, index) => {
        if (window === windowClass) {
            showHideWindows(window);
            activateElement(hiddenWindows[index]);
            window === "home-window" ||
            window === "setting-window" ||
            window === "paymentInfo-window" ||
            window === "chat-window"
                ? registerBtn.classList.add("reg-btn-none")
                : registerBtn.classList.remove("reg-btn-none");
            if (!isManager && window !== "poll-window") {
                registerBtn.classList.add("reg-btn-none");
            }
            changeRegisterIcon(window);
        } else {
            hideWindows(window);
            deactivateElement(hiddenWindows[index]);
        }
    });
}

function changeRegisterIcon(window) {
    const registerBtn = document.querySelector(".register-btn");
    if (window === "notification-window")
        registerBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
    class="feather feather-bell">
    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
    <path d="M13.73 21a2 2 0 0 1-3.46 0" />
</svg>`;
    else if (window === "poll-window")
        registerBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 22 22" stroke-width="2"
stroke-linecap="round" stroke-linejoin="round">
<path fill="currentColor"
    d="M8 4a2 2 0 1 1 4 0v12a2 2 0 1 1-4 0V4Zm2-1a1 1 0 0 0-1 1v12a1 1 0 1 0 2 0V4a1 1 0 0 0-1-1Zm-8 9a2 2 0 1 1 4 0v4a2 2 0 1 1-4 0v-4Zm2-1a1 1 0 0 0-1 1v4a1 1 0 1 0 2 0v-4a1 1 0 0 0-1-1Zm12-5a2 2 0 0 0-2 2v8a2 2 0 1 0 4 0V8a2 2 0 0 0-2-2Zm-1 2a1 1 0 1 1 2 0v8a1 1 0 1 1-2 0V8Z" />
</svg>`;
    else if (window === "cost-window") {
        registerBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" stroke-width="2" fill="currentColor"
    class="bi bi-coin" viewBox="0 0 16 16">
    <path
        d="M5.5 9.511c.076.954.83 1.697 2.182 1.785V12h.6v-.709c1.4-.098 2.218-.846 2.218-1.932 0-.987-.626-1.496-1.745-1.76l-.473-.112V5.57c.6.068.982.396 1.074.85h1.052c-.076-.919-.864-1.638-2.126-1.716V4h-.6v.719c-1.195.117-2.01.836-2.01 1.853 0 .9.606 1.472 1.613 1.707l.397.098v2.034c-.615-.093-1.022-.43-1.114-.9H5.5zm2.177-2.166c-.59-.137-.91-.416-.91-.836 0-.47.345-.822.915-.925v1.76h-.005zm.692 1.193c.717.166 1.048.435 1.048.91 0 .542-.412.914-1.135.982V8.518l.087.02z" />
    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
    <path d="M8 13.5a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11zm0 .5A6 6 0 1 0 8 2a6 6 0 0 0 0 12z" />
</svg>`;
    } else if (window === "charge-window") {
        registerBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" stroke-width="2" fill="currentColor"
        class="bi bi-credit-card" viewBox="0 0 16 16">
        <path
            d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1H2zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7z" />
        <path d="M2 10a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1v-1z" />
    </svg>`;
    }
}

// display none for one grid pages like home-window
const registerBtn = document.querySelector(".register-btn");
if (home.classList.contains("active"))
    registerBtn.classList.add("reg-btn-none");

// upload notification's picture
function previewImage(event) {
    let input = event.target;
    const uploadedImage = document.getElementById("uploaded-image");
    const imagePreview = document.getElementById("image-preview");
    const dropzoneFileLabel = document.querySelector(".dropzoneFileLabel");
    const removeFileBtn = document.querySelector(".removeFileBtn");
    if (input.files && input.files[0]) {
        let reader = new FileReader();
        reader.onload = function (e) {
            uploadedImage.src = e.target.result;
            uploadedImage.style.display = "block";
            imagePreview.style.display = "block";
            dropzoneFileLabel.classList.add("hidden");
            removeFileBtn.classList.remove("hidden");
            removeFileBtn.style = "margin-top: 0";
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeFile() {
    const removeFileBtn = document.querySelector(".removeFileBtn");
    const uploadedImage = document.getElementById("uploaded-image");
    const dropzoneFileLabel = document.querySelector(".dropzoneFileLabel");
    const imagePreview = document.getElementById("image-preview");
    const input = document.getElementById("dropzone-file");

    uploadedImage.src = "";
    uploadedImage.style.display = "none";
    imagePreview.style.display = "none";
    dropzoneFileLabel.classList.remove("hidden");
    removeFileBtn.classList.add("hidden");
    input.value = "";
}

const copyButton = document.getElementById("copyButton");
const linkInput = document.querySelector(".complex-group-link");

if (copyButton) {
    copyButton.addEventListener("click", () => {
        linkInput.select();
        navigator.clipboard.writeText(linkInput.value);

        const originalButtonText = copyButton.innerText;

        copyButton.classList.remove("bg-indigo-500", "hover:bg-indigo-600");
        copyButton.classList.add("bg-green-500", "hover:bg-green-600");
        copyButton.innerText = "کپی شد.";

        setTimeout(() => {
            copyButton.classList.remove("bg-green-500", "hover:bg-green-600");
            copyButton.classList.add("bg-indigo-500", "hover:bg-indigo-600");
            copyButton.innerText = originalButtonText;
        }, 3000);
    });
}
let optionCount = 0;

function addOption() {
    const optionWrapper = document.createElement("div");
    optionWrapper.classList.add(
        "search-wrapper",
        "add-wrapper",
        "optionWrapper"
    );

    const input = document.createElement("input");
    input.classList.add("search-input", "add-input");
    input.type = "text";
    input.name = `option${optionCount + 1}`;
    input.placeholder = `گزینه ${optionCount + 1}`;

    optionWrapper.appendChild(input);
    document.querySelector(".button-container").before(optionWrapper);

    optionCount++;
}

function removeOption() {
    const alertElement = document.getElementById("myAlert");

    if (optionCount > 2) {
        const optionWrappers = document.querySelectorAll(".optionWrapper");
        const lastOptionWrapper = optionWrappers[optionWrappers.length - 1];
        lastOptionWrapper.parentNode.removeChild(lastOptionWrapper);
        optionCount--;
    } else {
        alertElement.style.display = "flex";
    }
    if (alertElement) setTimeout(closeAlert, 3000);
}

const modalVote = document.querySelector(".modal-vote");
const openVoteBtns = document.querySelectorAll(".open-Btn-vote");
const closeVoteBtn = document.querySelector(".close-Btn-vote");
const optionWrapper = document.querySelector(".option-wrapper");

openVoteBtns.forEach((openBtn) => {
    openBtn.addEventListener("click", (event) => {
        optionWrapper.textContent = "";
        modalVote.classList.remove("hidden");
        const parentElement = event.target.parentNode.previousElementSibling;
        const pElements = parentElement.querySelectorAll("p");

        pElements.forEach((pElement) => {
            const className = pElement.className;
            const matches = className.match(/pollOption_(\d+)/);

            if (matches) {
                const optionNumber = matches[1];

                const divElement = document.createElement("div");
                const labelElement = document.createElement("label");
                const inputElement = document.createElement("input");
                const spanElement = document.createElement("span");

                divElement.classList.add(
                    "relative",
                    "flex",
                    "cursor-pointer",
                    "items-center",
                    "rounded-full",
                    "p-3",
                    "gap-2"
                );
                labelElement.setAttribute("for", matches[0]);
                inputElement.setAttribute("type", "radio");
                inputElement.setAttribute("id", matches[0]);
                inputElement.setAttribute("name", "voteRadio");
                inputElement.setAttribute("value", optionNumber);
                spanElement.classList.add("ml-2");
                spanElement.textContent = pElement.textContent;

                labelElement.appendChild(inputElement);
                labelElement.appendChild(spanElement);
                divElement.appendChild(labelElement);

                optionWrapper.appendChild(divElement);
            }
        });
    });
});

closeVoteBtn.addEventListener("click", () => {
    modalVote.classList.add("hidden");
});

const modalVoteResult = document.querySelector(".modal-vote-result");
const openVoteResultBtns = document.querySelectorAll(".open-Btn-vote-result");
const closeVoteResultBtn = document.querySelector(".close-Btn-vote-result");
const canvas = document.querySelector("#voteChart");
let currentVoteChart = null;

openVoteResultBtns.forEach((openBtn) => {
    openBtn.addEventListener("click", async (event) => {
        modalVoteResult.classList.remove("hidden");

        const CSRF_TOKEN = document.getElementById("csrf-token").value;
        const pollId = event.target.nextElementSibling;
        const params = "poll_id=" + pollId.textContent.trim();

        try {
            const response = await fetch("/dashboard/building/vote/Result", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": CSRF_TOKEN,
                    "Content-type": "application/x-www-form-urlencoded",
                },
                body: params,
            });

            if (response.ok) {
                const results = await response.json();

                const xValues = [];
                const yValues = [];
                const barColors = [];

                for (let i = 0; i < results.length; i++) {
                    xValues.push(results[i].option);
                    yValues.push(results[i].voteCount);
                    const color = randomColor();
                    barColors.push(color);
                }

                if (currentVoteChart) {
                    currentVoteChart.destroy();
                }

                currentVoteChart = new Chart("voteChart", {
                    type: "doughnut",
                    data: {
                        labels: xValues,
                        datasets: [
                            {
                                backgroundColor: barColors,
                                data: yValues,
                            },
                        ],
                    },
                });
            } else {
                throw new Error("Network response was not ok.");
            }
        } catch (error) {
            console.error("Error:", error);
        }
    });
});

closeVoteResultBtn.addEventListener("click", () => {
    modalVoteResult.classList.add("hidden");
});

const costModal = document.querySelector(".cost-modal");

const openCostBtns = document.querySelectorAll(".cost-open-Btn");
const closeCostBtn = document.querySelector(".cost-close-Btn");
const costInvoice = document.querySelector("#CostInvoice");

openCostBtns.forEach((openCostBtn) => {
    openCostBtn.addEventListener("click", (event) => {
        costModal.classList.remove("hidden");
        costInvoice.src = event.target.nextElementSibling.textContent;
    });
});

closeCostBtn.addEventListener("click", () => {
    costModal.classList.add("hidden");
});

const costPayModal = document.querySelector(".cost-pay-modal");

const openCostPayBtns = document.querySelectorAll(".cost-pay-open-Btn");
const closeCostPayBtn = document.querySelector(".cost-pay-close-Btn");
const incidentalCostId = document.querySelector("#incidentalCostId");

openCostPayBtns.forEach((openCostPayBtn) => {
    openCostPayBtn.addEventListener("click", (event) => {
        costPayModal.classList.remove("hidden");
        incidentalCostId.value = event.target.nextElementSibling.textContent;
    });
});

closeCostPayBtn.addEventListener("click", () => {
    costPayModal.classList.add("hidden");
});

const chargePayModal = document.querySelector(".charge-pay-modal");

const openChargePayBtns = document.querySelectorAll(".charge-pay-open-Btn");
const closeChargePayBtn = document.querySelector(".charge-pay-close-Btn");
const monthlyChargeId = document.querySelector("#monthlyChargeId");

openChargePayBtns.forEach((openChargePayBtn) => {
    openChargePayBtn.addEventListener("click", (event) => {
        chargePayModal.classList.remove("hidden");
        monthlyChargeId.value = event.target.nextElementSibling.textContent;
    });
});

closeChargePayBtn.addEventListener("click", () => {
    chargePayModal.classList.add("hidden");
});

const paymentInfo = document.querySelector("#paymentInfo");
paymentInfo.addEventListener("click", () => {
    paymentsInformation();
});

const paymentsInformation = async () => {
    const CSRF_TOKEN = document.getElementById("csrf-token").value;
    const complexId = document
        .querySelector(".complex-id-payments-info")
        .textContent.trim();
    const params = "complex_id=" + complexId;

    const response = await fetch("/dashboard/building/payments", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": CSRF_TOKEN,
            "Content-type": "application/x-www-form-urlencoded",
        },
        body: params,
    });

    if (response.ok) {
        const results = await response.json();

        const mainContainer = document.querySelector(
            ".building-boxes.jsListView.paymentInfo"
        );
        mainContainer.innerHTML = "";

        if (results.length === 0) {
            const emptyMessage = document.createElement("div");
            emptyMessage.classList.add(
                "bg-red-100",
                "border",
                "border-red-400",
                "text-red-700",
                "px-4",
                "py-2",
                "rounded"
            );
            emptyMessage.textContent = "پرداختی صورت نگرفته است.";

            mainContainer.appendChild(emptyMessage);
        } else {
            results.forEach((payment) => {
                const buildingBoxWrapper = document.createElement("div");
                buildingBoxWrapper.classList.add("building-box-wrapper");

                const buildingBox = document.createElement("div");
                buildingBox.classList.add("building-box");
                buildingBox.style.backgroundColor = "#fee4cb";

                const buildingBoxContentHeader = document.createElement("div");
                buildingBoxContentHeader.classList.add(
                    "building-box-content-header"
                );

                const nameElement = document.createElement("p");
                nameElement.classList.add("box-content-header");
                nameElement.textContent = payment.user_name;

                const unitNumberElement = document.createElement("p");
                unitNumberElement.classList.add("box-content-header");
                unitNumberElement.textContent =
                    "شماره واحد: " + payment.unit_number;

                const paymentTypeElement = document.createElement("p");
                paymentTypeElement.classList.add("box-content-header");
                paymentTypeElement.textContent = "نوع پرداختی: " + payment.type;

                const boxAddressWrapper = document.createElement("div");
                boxAddressWrapper.classList.add(
                    "box-address-wrapper",
                    "overflow-auto"
                );

                const paymentDateElement = document.createElement("p");
                paymentDateElement.classList.add("box-address-header", "p-4");
                paymentDateElement.textContent =
                    "تاریخ پرداخت: " + payment.date;

                const buildingBoxFooter = document.createElement("div");
                buildingBoxFooter.classList.add(
                    "building-box-footer",
                    "flex",
                    "justify-center",
                    "gap-2.5"
                );

                const paymentAmountElement = document.createElement("p");
                paymentAmountElement.classList.add("box-content-header");
                paymentAmountElement.textContent =
                    "مبلغ پرداختی: " + payment.amount;

                buildingBoxContentHeader.appendChild(nameElement);
                buildingBoxContentHeader.appendChild(unitNumberElement);
                buildingBoxContentHeader.appendChild(paymentTypeElement);

                boxAddressWrapper.appendChild(paymentDateElement);

                buildingBoxFooter.appendChild(paymentAmountElement);

                buildingBox.appendChild(buildingBoxContentHeader);
                buildingBox.appendChild(boxAddressWrapper);
                buildingBox.appendChild(buildingBoxFooter);

                buildingBoxWrapper.appendChild(buildingBox);
                mainContainer.appendChild(buildingBoxWrapper);
            });
        }
    }
};

// Group

let fromFirstMessageId = null;
let fromLastMessageId = null;

let currentFromFirstMessageId = null;
let currentFromLastMessageId = null;

const chat = document.querySelector("#chat");
const handleChatClick = async () => {
    const messagesContainer = document.querySelector(".messagesContainer");
    messagesContainer.innerHTML = "";
    currentDate = null;
    const lastMessageSeenId = await getLastMessageSeenId();
    await getMessages(lastMessageSeenId);
    scrollToMessage(lastMessageSeenId);
};

function scrollToMessage(lastMessageSeenId) {
    const chatContainer = document.querySelector(".chatContainer");
    const message = document.querySelector(`#message-${lastMessageSeenId}`);

    if (message) {
        const messageOffsetTop = message.offsetTop;
        const messageHeight = message.clientHeight;
        const chatContainerHeight = chatContainer.clientHeight;
        const scrollToPosition =
            messageOffsetTop + messageHeight / 2 - chatContainerHeight / 2;
        chatContainer.scrollTo({ top: scrollToPosition, behavior: "smooth" });
    }
}

chat.addEventListener("click", handleChatClick);

const getLastMessageSeenId = async () => {
    const complexId = document
        .querySelector(".complex-id-payments-info")
        .textContent.trim();

    const response = await fetch(
        `/dashboard/building/${complexId}/lastMessageSeenId`,
        {
            method: "GET",
        }
    );

    if (response.ok) {
        const data = await response.json();
        return data.last_message_seen_id;
    }
};

const textareaInput = document.querySelector(".textarea-input");
const sendBtn = document.querySelector(".sendBtn");
textareaInput.addEventListener("input", () => {
    if (textareaInput.value.trim() != "") {
        sendBtn.classList.add("cursor-pointer", "bg-green-500", "text-white");
    } else
        sendBtn.classList.remove(
            "cursor-pointer",
            "bg-green-500",
            "text-white"
        );
    sendBtn.classList.add("text-[#acacbe]");
});

const sendMessage = async () => {
    const CSRF_TOKEN = document.getElementById("csrf-token").value;
    const messageText = document.querySelector(
        'textarea[name="messageText"]'
    ).value;
    const messageImage = document.querySelector('input[name="messageImage"]')
        .files[0];
    const complexId = document
        .querySelector(".complex-id-payments-info")
        .textContent.trim();

    const params = new FormData();
    params.append("messageText", messageText);
    params.append("messageImage", messageImage);
    params.append("complexId", complexId);
    if (messageText || messageImage) {
        messageForm.reset();
        closeOperationBoxContainer();
        const response = await fetch("/dashboard/building/group/send", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": CSRF_TOKEN,
            },
            body: params,
        });
    }
};

const sendEditedMessage = async () => {
    const CSRF_TOKEN = document.getElementById("csrf-token").value;
    const messageText = document
        .querySelector('textarea[name="messageText"]')
        .value.trim();

    const params = new FormData();
    params.append("messageText", messageText);
    params.append("messageId", editingMessage.messageId);

    if (messageText) {
        if (messageText != editingMessage.currentText) {
            messageForm.reset();
            const response = await fetch(`/dashboard/building/group/edit`, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": CSRF_TOKEN,
                },
                body: params,
            });
        }
        closeOperationBoxContainer();
    }
};

const handleKeyDown = (event) => {
    if (event.key === "Enter" && !event.shiftKey) {
        event.preventDefault();
        if (requestEditingMessage) sendEditedMessage();
        else sendMessage();
    }
};

const handleSendButtonClick = () => {
    if (requestEditingMessage) sendEditedMessage();
    else sendMessage();
};

const messageForm = document.querySelector("#messageForm");
messageForm.addEventListener("keydown", handleKeyDown);

const sendButton = document.querySelector(".sendBtn");
sendButton.addEventListener("click", handleSendButtonClick);

const fileInput = document.getElementById("file-upload");
const showOperationBoxContainer = document.getElementById(
    "showOperationBoxContainer"
);
const textOperationBox = document.getElementById("textOperationBox");
const closeButton = document.getElementById("closeButton");

fileInput.addEventListener("change", (event) => {
    const file = event.target.files[0];
    if (file) {
        textOperationBox.textContent = file.name;
        showOperationBoxContainer.style.display = "flex";
        closeButton.style.display = "flex";
    } else {
        textOperationBox.textContent = "";
        showOperationBoxContainer.style.display = "none";
        closeButton.style.display = "none";
    }
});

closeButton.addEventListener("click", closeOperationBoxContainer);

function closeOperationBoxContainer() {
    const fileUploadLabel = document.querySelector(".file-upload-label");
    const textArea = (document.querySelector(
        'textarea[name="messageText"]'
    ).value = "");
    textOperationBox.textContent = "";
    fileInput.value = "";
    editingMessage = {};
    showOperationBoxContainer.style.display = "none";
    closeButton.style.display = "none";
    requestEditingMessage = false;
    fileUploadLabel.classList.remove("hidden");
    sendBtn.classList.remove("cursor-pointer", "bg-green-500", "text-white");
    sendBtn.classList.add("text-[#acacbe]");
}

let currentDate = null;
let currentFirstDate = null;
const usersInfo = [];

const getUserColor = (userId) => {
    let userInfo = usersInfo.find((user) => user.userId === userId);

    if (!userInfo) {
        const userColor = randomColor();
        userInfo = { userId: userId, userColor };
        usersInfo.push(userInfo);
    }

    return userInfo.userColor;
};

const displayMessages = (messages, first = false) => {
    const messagesContainer = document.querySelector(".messagesContainer");
    const dates = document.querySelectorAll(".messageDateContainer p");
    if (dates.length) {
        let lengthDate = dates.length;
        currentDate = first
            ? dates[0].textContent
            : dates[lengthDate - 1].textContent;
    }
    messages.forEach((message) => {
        const userColor = getUserColor(message.user_id);
        const formattedDate = getDate(message.created_at);
        if (formattedDate != currentDate) {
            const messageDateContainer = document.createElement("div");
            messageDateContainer.classList.add(
                "flex",
                "justify-center",
                "mb-2",
                "messageDateContainer"
            );
            const messageDateBox = document.createElement("div");
            messageDateBox.classList.add(
                "rounded",
                "py-2",
                "px-4",
                "bg-[#ddecf2]"
            );
            const messageDate = document.createElement("p");
            messageDate.classList.add("text-sm", "uppercase");
            messageDate.textContent = formattedDate;
            currentDate = formattedDate;
            messageDateBox.appendChild(messageDate);
            messageDateContainer.appendChild(messageDateBox);
            if (first)
                messagesContainer.insertBefore(
                    messageDateContainer,
                    messagesContainer.children[0]
                );
            else messagesContainer.appendChild(messageDateContainer);
        }

        const messageDiv = document.createElement("div");
        messageDiv.classList.add("flex", "mb-2");
        messageDiv.id = `message-${message.message_id}`;

        const messageContentDiv = document.createElement("div");
        messageContentDiv.classList.add(
            "rounded",
            "py-2",
            "px-3",
            "max-w-full",
            "break-words",
            "messageContent",
            "relative",
            "inline-block"
        );
        const dropDownMenu = createDropDownMenu();
        if (message.is_owned_by_logged_in_user) {
            messageContentDiv.classList.add("bg-[#e2f7cb]");
            dropDownMenu.classList.add("right-20");
        } else {
            messageContentDiv.classList.add("bg-[#f2f2f2]");
            messageDiv.classList.add("justify-end");
            dropDownMenu.classList.add("left-20");
            dropDownMenu.children[0].children[0].children[1].classList.add(
                "hidden"
            );
            dropDownMenu.children[0].children[0].children[2].classList.add(
                "hidden"
            );
            dropDownMenu.children[0].children[0].children[3].classList.add(
                "hidden"
            );
        }

        messageContentDiv.appendChild(dropDownMenu);

        const userName = document.createElement("p");
        userName.classList.add(
            "text-sm",
            `text-[${userColor}]`,
            "font-bold",
            "message-user-name"
        );
        userName.textContent = message.user_name;

        const imageContainer = document.createElement("div");
        imageContainer.classList.add("max-w-lg", "mb-2");
        const messageImage = document.createElement("img");

        if (message.image_url) {
            messageImage.src = `/storage/chats/${message.image_url}`;
            messageImage.alt = "";
            messageImage.classList.add(
                "max-w-full",
                "h-auto",
                "rounded-lg",
                "shadow-md",
                "mb-4"
            );
        }
        const messageFooterContainer = document.createElement("div");
        messageFooterContainer.classList.add(
            "flex",
            "justify-between",
            "items-center",
            "gap-3",
            "messageFooterContainer"
        );

        const messageText = document.createElement("p");
        messageText.classList.add(
            "text-sm",
            "mt-1",
            "whitespace-pre-wrap",
            "text-gray-800",
            "break-words",
            "message-text"
        );
        messageText.textContent = message.text;

        const messageTime = document.createElement("p");
        messageTime.classList.add(
            "text-right",
            "text-xs",
            "text-gray-500",
            "mt-3",
            "message-time"
        );
        const formattedTime = getTime(message.created_at);
        messageTime.textContent = formattedTime;

        const messageEdited = document.createElement("p");
        messageEdited.classList.add(
            "text-left",
            "text-xs",
            "text-gray-500",
            "mt-3",
            "hidden",
            "message-edited"
        );
        if (message.is_edited) messageEdited.classList.remove("hidden");

        messageEdited.textContent = "ویرایش شده";

        if (!message.is_owned_by_logged_in_user)
            messageContentDiv.appendChild(userName);

        if (message.image_url) {
            imageContainer.appendChild(messageImage);
            messageContentDiv.appendChild(imageContainer);
        }
        messageContentDiv.addEventListener("click", handlerMessage);

        messageContentDiv.appendChild(messageText);
        messageFooterContainer.appendChild(messageTime);
        messageFooterContainer.appendChild(messageEdited);

        messageContentDiv.appendChild(messageFooterContainer);

        messageDiv.appendChild(messageContentDiv);
        if (first)
            messagesContainer.insertBefore(
                messageDiv,
                messagesContainer.children[1]
            );
        else messagesContainer.appendChild(messageDiv);
    });
};

const getMessages = async (lastReceivedMessageId) => {
    const complexId = document
        .querySelector(".complex-id-payments-info")
        .textContent.trim();

    const response = await fetch(
        `/dashboard/building/${complexId}/group/${lastReceivedMessageId}`,
        {
            method: "GET",
        }
    );

    if (response.ok) {
        const data = await response.json();
        const messages = data.messages;
        fromFirstMessageId = data.first_message_id;
        fromLastMessageId = data.last_message_id;

        if (messages.length) {
            displayMessages(messages);
        }
    }
};

const getMessagesBeforeMessageId = async (firstMessageId) => {
    const complexId = document
        .querySelector(".complex-id-payments-info")
        .textContent.trim();
    currentFromFirstMessageId = firstMessageId;
    const response = await fetch(
        `/dashboard/building/getMessagesBeforeFrom/${complexId}/${firstMessageId}`,
        {
            method: "GET",
        }
    );

    if (response.ok) {
        const data = await response.json();
        const messages = data.messages;
        fromFirstMessageId = data.first_message_id;
        if (messages.length) {
            displayMessages(messages.reverse(), true);
        }
    }
};

const getMessagesAfterMessageId = async (lastMessageId) => {
    const complexId = document
        .querySelector(".complex-id-payments-info")
        .textContent.trim();
    currentFromLastMessageId = lastMessageId;
    const response = await fetch(
        `/dashboard/building/getMessagesAfterFrom/${complexId}/${lastMessageId}`,
        {
            method: "GET",
        }
    );

    if (response.ok) {
        const data = await response.json();
        const messages = data.messages;
        fromLastMessageId = data.last_message_id;

        if (messages.length) {
            displayMessages(messages);
        }
    }
};

function getTime(created_at) {
    const createdAt = new Date(created_at);
    const options = {
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
        hourCycle: "h24",
    };
    const formattedTime = createdAt.toLocaleTimeString("fa-IR", options);
    return formattedTime;
}

function getDate(created_at) {
    if (isToday(created_at)) return "امروز";
    const createdAt = new Date(created_at);
    const options = { year: "numeric", month: "long", day: "numeric" };
    const formattedDate = createdAt.toLocaleDateString("fa-IR", options);
    return formattedDate;
}

function isToday(created_at) {
    const todayDate = new Date();
    const createdAt = new Date(created_at);

    const todayDay = todayDate.getDate();
    const todayMonth = todayDate.getMonth();
    const todayYear = todayDate.getFullYear();

    const createdDay = createdAt.getDate();
    const createdMonth = createdAt.getMonth();
    const createdYear = createdAt.getFullYear();

    return (
        todayDay === createdDay &&
        todayMonth === createdMonth &&
        todayYear === createdYear
    );
}

const createDropDownMenu = () => {
    const dropDownMenu = document.createElement("div");
    dropDownMenu.innerHTML = `<div role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1">
        <div class="py-1" role="none">
            <a href="#" class="text-gray-700 block px-4 py-2 text-sm hover:bg-gray-200" role="menuitem" tabindex="-1" id="menu-item-0">کپی</a>
            <a href="#" class="text-gray-700 block px-4 py-2 text-sm hover:bg-gray-200" role="menuitem" tabindex="-1" id="menu-item-1">ویرایش</a>
            <a href="#" class="text-gray-700 block px-4 py-2 text-sm hover:bg-gray-200" role="menuitem" tabindex="-1" id="menu-item-2">حذف</a>
            <a href="#" class="text-gray-700 block px-4 py-2 text-sm hover:bg-gray-200" role="menuitem" tabindex="-1" id="menu-item-3">مشاهده کنندگان</a>
        </div>
    </div>`;
    dropDownMenu.classList.add(
        "absolute",
        "z-10",
        "mt-2",
        "w-56",
        "origin-top-right",
        "divide-y",
        "divide-gray-100",
        "rounded-md",
        "bg-white",
        "shadow-lg",
        "ring-1",
        "ring-black",
        "ring-opacity-5",
        "focus:outline-none",
        "drop-down",
        "hidden"
    );

    const copyLink = dropDownMenu.querySelector("#menu-item-0");
    const editLink = dropDownMenu.querySelector("#menu-item-1");
    const deleteLink = dropDownMenu.querySelector("#menu-item-2");
    const observersLink = dropDownMenu.querySelector("#menu-item-3");

    copyLink.addEventListener("click", () => {
        const textMessage = dropDownMenu.parentElement
            .querySelector(".message-text")
            .innerText.trim();
        navigator.clipboard.writeText(textMessage);
        showAlert("gray", "متن کپی شد.");
        dropDownMenu.classList.add("hidden");
    });

    editLink.addEventListener("click", () => {
        const messageElement = dropDownMenu.parentElement.parentElement.id;
        const messageId = messageElement.match(/\d+/)?.[0];
        editMessage(messageId);
        requestEditingMessage = true;
        dropDownMenu.classList.add("hidden");
    });

    deleteLink.addEventListener("click", () => {
        const messageElement = dropDownMenu.parentElement.parentElement.id;
        const messageId = messageElement.match(/\d+/)?.[0];
        deleteMessage(messageId);
        dropDownMenu.classList.add("hidden");
    });

    observersLink.addEventListener("click", (event) => {
        const messageViewerModal = document.querySelector(
            ".modal-message-viewer"
        );
        const viewerWrapper = document.querySelector(".viewer-wrapper");

        const messageId = extractNumericId(
            event.target.closest(".messageContent").parentElement.id
        );
        getMessageViewers(messageId)
            .then((result) => {
                viewerWrapper.innerHTML = "";

                result.forEach((viewer) => {
                    const divElement = document.createElement("div");
                    divElement.classList.add(
                        "bg-blue-200",
                        "rounded-lg",
                        "p-3",
                        "shadow-md",
                        "flex",
                        "items-center",
                        "justify-between",
                        "gap-20",
                        "hover:bg-blue-300"
                    );
                    divElement.innerHTML = `
                    <div class="flex items-center justify-center">
                        <p class="text-center">${viewer.user_name}</p>
                    </div>
                    <div class="flex gap-4">
                        <p class="text-center">${getDate(viewer.viewed_at)}</p>
                        <p class="text-center">${getTime(viewer.viewed_at)}</p>
                    </div>
                    `;

                    viewerWrapper.appendChild(divElement);
                });
                messageViewerModal.classList.remove("hidden");
            })
            .catch((error) => {
                console.error("خطا در دریافت اطلاعات: ", error);
            });
        dropDownMenu.classList.add("hidden");
    });

    dropDownMenu.addEventListener("click", (event) => {
        event.stopPropagation();
    });

    return dropDownMenu;
};

const handlerMessage = (event) => {
    event.stopPropagation();

    const messageContentDivs = document.querySelectorAll(".messageContent");
    messageContentDivs.forEach((messageContentDiv) => {
        if (messageContentDiv !== event.currentTarget) {
            messageContentDiv
                .querySelector(".drop-down")
                .classList.remove("block");
            messageContentDiv
                .querySelector(".drop-down")
                .classList.add("hidden");
        }
    });

    const dropDownMenu = event.currentTarget.querySelector(".drop-down");
    if (dropDownMenu.classList.contains("hidden")) {
        dropDownMenu.classList.remove("hidden");
        dropDownMenu.classList.add("block");
    } else {
        dropDownMenu.classList.remove("block");
        dropDownMenu.classList.add("hidden");
    }
};

const messageContentDivs = document.querySelectorAll(".messageContent");
messageContentDivs.forEach((messageContentDiv) => {
    messageContentDiv.addEventListener("click", handlerMessage);
});

function showAlert(color, message) {
    const alertContainer = document.getElementById("alertContainer");

    const alertDiv = document.createElement("div");
    alertDiv.classList.add(
        "alert-wrapper",
        `bg-${color}-300`,
        "p-3",
        "rounded-md",
        "shadow-md",
        "text-sm",
        `text-${color}-800`,
        "mb-2",
        "whitespace-nowrap",
        "transform",
        "translate-x-2.5"
    );
    alertDiv.textContent = message;

    alertContainer.appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

let requestEditingMessage = false;
let editingMessage = {
    currentText: "",
    messageId: null,
};

const editMessage = (messageId) => {
    const fileUploadLabel = document.querySelector(".file-upload-label");
    const messageElement = document.getElementById(`message-${messageId}`);
    if (messageElement) {
        const messageText = messageElement
            .querySelector(".message-text")
            .textContent.trim();
        textOperationBox.textContent = messageText;
        const textArea = document.querySelector('textarea[name="messageText"]');
        textArea.value = messageText;

        editingMessage.currentText = messageText;
        editingMessage.messageId = messageId;
        fileUploadLabel.classList.add("hidden");
        showOperationBoxContainer.style.display = "flex";
        closeButton.style.display = "flex";
    }
};

function editMessageFromGroup(message) {
    const messageElement = document.getElementById(`message-${message.id}`);
    if (messageElement) {
        messageElement.querySelector(".message-text").textContent =
            message.text;
        messageElement
            .querySelector(".message-edited")
            .classList.remove("hidden");
    }
}

const deleteMessage = async (messageId) => {
    deleteMessageFromGroup(messageId);
    const CSRF_TOKEN = document.getElementById("csrf-token").value;

    const response = await fetch(`/dashboard/building/group/${messageId}`, {
        method: "GET",
        headers: {
            "X-CSRF-TOKEN": CSRF_TOKEN,
        },
    });

    if (response.ok) {
        const data = await response.json();
        if (data.status === "403") showAlert("red", data.message);
    }
};

function deleteMessageFromGroup(messageId) {
    const messageElement = document.getElementById(`message-${messageId}`);
    if (messageElement) {
        messageElement.remove();
    }
}

function scrollToBottom() {
    const chatContainer = document.querySelector(".chatContainer");
    const scrollHeight = chatContainer.scrollHeight;
    chatContainer.scrollTop = scrollHeight;
}

const scrollToBottomBtn = document.querySelector(".scrollToBottomBtn");
scrollToBottomBtn.addEventListener("click", scrollToBottom);

const chatContainer = document.querySelector(".chatContainer");

let InitialScrollValue = chatContainer.scrollTop;
chatContainer.addEventListener("scroll", handleScroll);

let lastSeenMessageId = null;
function handleScroll() {
    const scrolled = chatContainer.scrollTop;
    const clientHeight = chatContainer.clientHeight;
    const messages = chatContainer.querySelectorAll(".messageContent");

    if (scrolled < 200 && InitialScrollValue >= scrolled)
        if (currentFromFirstMessageId != fromFirstMessageId)
            getMessagesBeforeMessageId(fromFirstMessageId);
    InitialScrollValue = scrolled;
    if (scrolled + clientHeight < chatContainer.scrollHeight - 20) {
        scrollToBottomBtn.classList.remove("hidden");
        if (currentFromLastMessageId != fromLastMessageId)
            getMessagesAfterMessageId(fromLastMessageId);
    } else scrollToBottomBtn.classList.add("hidden");

    for (let i = 0; i < messages.length; i++) {
        const message = messages[i];
        const messageTop = message.offsetTop;
        const messageHeight = message.offsetHeight;

        if (scrolled + clientHeight >= messageTop + messageHeight / 2) {
            const messageId = extractNumericId(
                message.parentElement.getAttribute("id")
            );

            if (messageId && messageId > lastSeenMessageId) {
                lastSeenMessageId = messageId;
                seen(lastSeenMessageId);
                break;
            }
        }
    }
}

function extractNumericId(str) {
    const regex = /^message-(\d+)/;
    const match = str.match(regex);
    if (match && match[1]) {
        return parseInt(match[1], 10);
    } else {
        return null;
    }
}

const seen = async (lastSeenMessageId) => {
    const CSRF_TOKEN = document.getElementById("csrf-token").value;
    const params = "messageId=" + lastSeenMessageId;

    const response = await fetch("/dashboard/building/group/seen", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": CSRF_TOKEN,
            "Content-type": "application/x-www-form-urlencoded",
        },
        body: params,
    });
};

const getMessageViewers = async (messageId) => {
    const CSRF_TOKEN = document.getElementById("csrf-token").value;
    const params = "message_id=" + messageId;

    const response = await fetch("/dashboard/building/getMessageViewers", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": CSRF_TOKEN,
            "Content-type": "application/x-www-form-urlencoded",
        },
        body: params,
    });

    if (response.ok) {
        const result = await response.json();
        return result;
    } else throw new Error("درخواست با خطا مواجه شد");
};

const userLogInId = document.querySelector(".userLogInId").textContent.trim();

const closeBtnMessageViewer = document.querySelector(
    ".close-Btn-message-viewer"
);

closeBtnMessageViewer.addEventListener("click", () => {
    const messageViewerModal = document.querySelector(".modal-message-viewer");
    messageViewerModal.classList.add("hidden");
});

const pusher = new Pusher("65cb2ae469a98ef3a8c2", {
    cluster: "ap2",
});

const channel = pusher.subscribe("group");

channel.bind("NewChatMessage", function (data) {
    const message = data.message;
    if (message.user_id == userLogInId)
        message.is_owned_by_logged_in_user = true;
    displayMessages([message]);
    if (message.user_id == userLogInId) scrollToBottom();
});

channel.bind("DeleteChatMessage", function (data) {
    const deletedMessage = data.deletedMessage;
    if (deletedMessage.user_id != userLogInId)
        deleteMessageFromGroup(deletedMessage.id);
});

channel.bind("EditChatMessage", function (data) {
    const editedMessage = data.editedMessage;
    editMessageFromGroup(editedMessage);
});
