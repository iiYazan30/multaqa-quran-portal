document.addEventListener("DOMContentLoaded", function () {
    initQuranProgressMap();
});

function initQuranProgressMap() {
    var mapElement = document.getElementById("surah-map");
    if (!mapElement) {
        return;
    }

    var surahs = [
        "الفاتحة",
        "البقرة",
        "آل عمران",
        "النساء",
        "المائدة",
        "الأنعام",
        "الأعراف",
        "الأنفال",
        "التوبة",
        "يونس",
        "هود",
        "يوسف",
        "الرعد",
        "إبراهيم",
        "الحجر",
        "النحل",
        "الإسراء",
        "الكهف",
        "مريم",
        "طه",
        "الأنبياء",
        "الحج",
        "المؤمنون",
        "النور",
        "الفرقان",
        "الشعراء",
        "النمل",
        "القصص",
        "العنكبوت",
        "الروم",
        "لقمان",
        "السجدة",
        "الأحزاب",
        "سبأ",
        "فاطر",
        "يس",
        "الصافات",
        "ص",
        "الزمر",
        "غافر",
        "فصلت",
        "الشورى",
        "الزخرف",
        "الدخان",
        "الجاثية",
        "الأحقاف",
        "محمد",
        "الفتح",
        "الحجرات",
        "ق",
        "الذاريات",
        "الطور",
        "النجم",
        "القمر",
        "الرحمن",
        "الواقعة",
        "الحديد",
        "المجادلة",
        "الحشر",
        "الممتحنة",
        "الصف",
        "الجمعة",
        "المنافقون",
        "التغابن",
        "الطلاق",
        "التحريم",
        "الملك",
        "القلم",
        "الحاقة",
        "المعارج",
        "نوح",
        "الجن",
        "المزمل",
        "المدثر",
        "القيامة",
        "الإنسان",
        "المرسلات",
        "النبأ",
        "النازعات",
        "عبس",
        "التكوير",
        "الانفطار",
        "المطففين",
        "الانشقاق",
        "البروج",
        "الطارق",
        "الأعلى",
        "الغاشية",
        "الفجر",
        "البلد",
        "الشمس",
        "الليل",
        "الضحى",
        "الشرح",
        "التين",
        "العلق",
        "القدر",
        "البينة",
        "الزلزلة",
        "العاديات",
        "القارعة",
        "التكاثر",
        "العصر",
        "الهمزة",
        "الفيل",
        "قريش",
        "الماعون",
        "الكوثر",
        "الكافرون",
        "النصر",
        "المسد",
        "الإخلاص",
        "الفلق",
        "الناس"
    ];

    var storageKey = "multaqaQuranProgressDemo";
    var completedIndexes = new Set(loadCompletedIndexes(storageKey, surahs.length));

    renderTiles(mapElement, surahs, completedIndexes, storageKey);
    updateSummary(surahs, completedIndexes);
}

function loadCompletedIndexes(storageKey, totalSurahs) {
    var defaultCompleted = [];
    var index;

    for (index = 0; index < 19; index += 1) {
        defaultCompleted.push(index);
    }

    try {
        var storedValue = window.localStorage.getItem(storageKey);
        var parsedValue = storedValue ? JSON.parse(storedValue) : null;

        if (!Array.isArray(parsedValue)) {
            return defaultCompleted;
        }

        return parsedValue.filter(function (item, itemIndex, list) {
            return Number.isInteger(item) &&
                item >= 0 &&
                item < totalSurahs &&
                list.indexOf(item) === itemIndex;
        });
    } catch (error) {
        return defaultCompleted;
    }
}

function renderTiles(mapElement, surahs, completedIndexes, storageKey) {
    var fragment = document.createDocumentFragment();

    surahs.forEach(function (surahName, index) {
        var tile = document.createElement("button");
        tile.type = "button";
        tile.className = "surah-tile " + getTileSizeClass(index);
        tile.setAttribute("data-surah-index", String(index));

        var number = document.createElement("span");
        number.className = "surah-number";
        number.textContent = formatArabicNumber(index + 1);

        var name = document.createElement("span");
        name.className = "surah-name";
        name.textContent = surahName;

        var status = document.createElement("span");
        status.className = "surah-status";
        status.setAttribute("aria-hidden", "true");
        status.textContent = "✓";

        tile.appendChild(number);
        tile.appendChild(name);
        tile.appendChild(status);

        setTileState(tile, surahName, completedIndexes.has(index));

        tile.addEventListener("click", function () {
            if (completedIndexes.has(index)) {
                completedIndexes.delete(index);
            } else {
                completedIndexes.add(index);
            }

            setTileState(tile, surahName, completedIndexes.has(index));
            saveCompletedIndexes(storageKey, completedIndexes);
            updateSummary(surahs, completedIndexes);
        });

        fragment.appendChild(tile);
    });

    mapElement.appendChild(fragment);
}

function setTileState(tile, surahName, isCompleted) {
    tile.classList.toggle("is-completed", isCompleted);
    tile.setAttribute("aria-pressed", isCompleted ? "true" : "false");
    tile.setAttribute("aria-label", surahName + " - " + (isCompleted ? "مكتملة" : "بانتظار الحفظ"));
}

function saveCompletedIndexes(storageKey, completedIndexes) {
    try {
        var sortedIndexes = Array.from(completedIndexes).sort(function (first, second) {
            return first - second;
        });
        window.localStorage.setItem(storageKey, JSON.stringify(sortedIndexes));
    } catch (error) {
        return;
    }
}

function updateSummary(surahs, completedIndexes) {
    var completedCount = completedIndexes.size;
    var totalCount = surahs.length;
    var percentage = Math.round((completedCount / totalCount) * 100);
    var latestCompletedIndex = getLatestCompletedIndex(completedIndexes);
    var latestCompletedName = latestCompletedIndex === null ? "لا يوجد" : surahs[latestCompletedIndex];

    setText("completed-surahs-count", formatArabicNumber(completedCount));
    setText("total-surahs-count", formatArabicNumber(totalCount));
    setText("completion-percentage", formatArabicNumber(percentage) + "%");
    setText("latest-completed-surah", latestCompletedName);

    var progressFill = document.getElementById("quran-progress-fill");
    if (progressFill) {
        progressFill.style.width = percentage + "%";
    }
}

function getLatestCompletedIndex(completedIndexes) {
    if (!completedIndexes.size) {
        return null;
    }

    return Array.from(completedIndexes).reduce(function (latestIndex, currentIndex) {
        return currentIndex > latestIndex ? currentIndex : latestIndex;
    }, -1);
}

function setText(elementId, value) {
    var element = document.getElementById(elementId);
    if (element) {
        element.textContent = value;
    }
}

function getTileSizeClass(index) {
    var largeTiles = [1, 6, 18, 35, 54, 66, 75, 96];
    var wideTiles = [0, 2, 3, 4, 5, 8, 11, 15, 17, 20, 23, 24, 25, 26, 27, 28, 32, 33, 34, 37, 39, 40, 41, 42, 45, 46, 47, 48, 56, 57, 58, 59, 60, 63, 64, 65, 76, 77, 82, 84, 88, 90, 94, 97, 98, 100, 102, 104, 106, 108, 109, 110, 111, 112, 113];
    var tallTiles = [7, 12, 14, 16, 21, 22, 30, 36, 43, 50, 51, 52, 53, 55, 62, 67, 68, 69, 71, 73, 74, 79, 80, 85, 89, 91, 92, 93, 95, 99, 101, 103, 105, 107];

    if (largeTiles.indexOf(index) !== -1) {
        return "is-large";
    }

    if (wideTiles.indexOf(index) !== -1) {
        return "is-wide";
    }

    if (tallTiles.indexOf(index) !== -1) {
        return "is-tall";
    }

    return "";
}

function formatArabicNumber(value) {
    return Number(value).toLocaleString("ar-EG");
}
