document.addEventListener("DOMContentLoaded", function () {
    var dashboardState = createDashboardState();

    renderSummaryStrip(dashboardState);
    renderQuickOverviewTable(dashboardState);
    renderFollowupPreview(dashboardState);
    renderWeeklyReport(dashboardState);
    renderWeeklyPerformance(dashboardState.weeklyPerformance);
    renderCumulativeStats(dashboardState.students);
    renderStudentsPage(dashboardState.students);
    renderFollowupTable(dashboardState.students);

    initWeeklyReportInteractions(dashboardState);
    initSectionNavigation();
});

function createDashboardState() {
    return {
        halqaName: "حلقة النور",
        supervisorName: "أحمد يوسف",
        weeklyPerformance: [
            { week: "الأسبوع 7", rate: 72, heard: 5, pages: 22 },
            { week: "الأسبوع 8", rate: 86, heard: 6, pages: 30 },
            { week: "الأسبوع 9", rate: 74, heard: 5, pages: 24 },
            { week: "الأسبوع 10", rate: 100, heard: 7, pages: 38 },
            { week: "الأسبوع 11", rate: 86, heard: 6, pages: 29 },
            { week: "الأسبوع 12", rate: 71, heard: 5, pages: 26 }
        ],
        students: [
            {
                id: 1,
                name: "عبد الله نزار",
                level: "متقدم",
                attendance: "منتظم",
                engagement: "عال",
                status: "جيد",
                lastRecitationDate: "2026-04-09",
                lastActiveWeek: "الأسبوع 12",
                cumulativeHifz: 62,
                cumulativeReview: 84,
                cumulativeSessions: 23,
                activeWeeks: 11,
                followUpReasons: [],
                actionSuggestion: "استمرار بنفس النسق",
                batches: [
                    { type: "hifz", from: 18, to: 19, notes: "إتقان جيد" },
                    { type: "review", from: 30, to: 31, notes: "يحتاج تثبيت مواضع الوقف" }
                ]
            },
            {
                id: 2,
                name: "يوسف رائد",
                level: "متوسط",
                attendance: "متذبذب",
                engagement: "متوسط",
                status: "متابعة",
                lastRecitationDate: "2026-03-31",
                lastActiveWeek: "الأسبوع 10",
                cumulativeHifz: 31,
                cumulativeReview: 57,
                cumulativeSessions: 18,
                activeWeeks: 9,
                followUpReasons: ["لم يسمع هذا الأسبوع", "ضعف في الالتزام"],
                actionSuggestion: "اتصال بولي الأمر وتحديد خطة التزام",
                batches: []
            },
            {
                id: 3,
                name: "سليم مؤيد",
                level: "مبتدئ",
                attendance: "منتظم",
                engagement: "متوسط",
                status: "متابعة",
                lastRecitationDate: "2026-04-08",
                lastActiveWeek: "الأسبوع 12",
                cumulativeHifz: 22,
                cumulativeReview: 29,
                cumulativeSessions: 14,
                activeWeeks: 8,
                followUpReasons: ["عدد صفحات قليل"],
                actionSuggestion: "رفع هدف الحفظ إلى صفحتين أسبوعيا",
                batches: [
                    { type: "hifz", from: 44, to: 44, notes: "صحيح مع بعض التردد" }
                ]
            },
            {
                id: 4,
                name: "براء خالد",
                level: "متوسط",
                attendance: "جيد",
                engagement: "عال",
                status: "جيد",
                lastRecitationDate: "2026-04-10",
                lastActiveWeek: "الأسبوع 12",
                cumulativeHifz: 40,
                cumulativeReview: 64,
                cumulativeSessions: 20,
                activeWeeks: 10,
                followUpReasons: [],
                actionSuggestion: "تعزيز المراجعة الممتدة",
                batches: [
                    { type: "review", from: 55, to: 58, notes: "ممتاز" }
                ]
            },
            {
                id: 5,
                name: "أنس وليد",
                level: "متقدم",
                attendance: "متكرر الغياب",
                engagement: "ضعيف",
                status: "تنبيه",
                lastRecitationDate: "2026-04-05",
                lastActiveWeek: "الأسبوع 11",
                cumulativeHifz: 48,
                cumulativeReview: 52,
                cumulativeSessions: 17,
                activeWeeks: 7,
                followUpReasons: ["غياب متكرر", "تراجع في التقدم"],
                actionSuggestion: "جلسة متابعة فردية وخطة استدراك",
                batches: [
                    { type: "hifz", from: 72, to: 72, notes: "أداء منخفض" }
                ]
            },
            {
                id: 6,
                name: "مالك طه",
                level: "متوسط",
                attendance: "منتظم",
                engagement: "عال",
                status: "جيد",
                lastRecitationDate: "2026-04-10",
                lastActiveWeek: "الأسبوع 12",
                cumulativeHifz: 36,
                cumulativeReview: 41,
                cumulativeSessions: 16,
                activeWeeks: 10,
                followUpReasons: [],
                actionSuggestion: "الاستمرار",
                batches: [
                    { type: "hifz", from: 80, to: 81, notes: "إتقان جيد" },
                    { type: "review", from: 90, to: 91, notes: "ثبات جيد" }
                ]
            },
            {
                id: 7,
                name: "حمزة شادي",
                level: "مبتدئ",
                attendance: "جيد",
                engagement: "متوسط",
                status: "متابعة",
                lastRecitationDate: "2026-04-07",
                lastActiveWeek: "الأسبوع 12",
                cumulativeHifz: 18,
                cumulativeReview: 25,
                cumulativeSessions: 13,
                activeWeeks: 8,
                followUpReasons: ["يحتاج مراجعة"],
                actionSuggestion: "تركيز على تثبيت المحفوظ الحالي",
                batches: [
                    { type: "review", from: 102, to: 103, notes: "يحتاج ضبط أحكام" }
                ]
            }
        ]
    };
}

function renderSummaryStrip(state) {
    var container = document.getElementById("summary-strip");

    if (!container) {
        return;
    }

    var totalStudents = state.students.length;
    var studentsWithRecitation = state.students.filter(function (student) {
        return getStudentWeeklyTotals(student).total > 0;
    }).length;
    var followupCount = state.students.filter(function (student) {
        return student.followUpReasons.length > 0;
    }).length;
    var recitationRate = totalStudents ? Math.round((studentsWithRecitation / totalStudents) * 100) : 0;

    var items = [
        { label: "اسم الحلقة", value: state.halqaName },
        { label: "عدد الطلاب", value: String(totalStudents) },
        { label: "نسبة التسميع هذا الأسبوع", value: recitationRate + "%" },
        { label: "عدد الطلاب الذين سمعوا", value: String(studentsWithRecitation) },
        { label: "طلاب يحتاجون متابعة", value: String(followupCount) }
    ];

    container.innerHTML = items.map(function (item) {
        return "<article class=\"summary-item\"><h3>" + escapeHtml(item.value) + "</h3><p>" + escapeHtml(item.label) + "</p></article>";
    }).join("");
}

function renderQuickOverviewTable(state) {
    var tbody = document.getElementById("quick-overview-body");

    if (!tbody) {
        return;
    }

    tbody.innerHTML = state.students.map(function (student) {
        var totals = getStudentWeeklyTotals(student);
        var latestBatch = getLatestBatch(student);
        var statusClass = getStatusClass(student.status);

        return "<tr>" +
            "<td>" + escapeHtml(student.name) + "</td>" +
            "<td>" + escapeHtml(formatDateArabic(student.lastRecitationDate)) + "</td>" +
            "<td>" + escapeHtml(latestBatch ? batchTypeLabel(latestBatch.type) : "-") + "</td>" +
            "<td>" + (latestBatch ? escapeHtml(String(latestBatch.from)) : "-") + "</td>" +
            "<td>" + (latestBatch ? escapeHtml(String(latestBatch.to)) : "-") + "</td>" +
            "<td>" + escapeHtml(String(totals.total)) + "</td>" +
            "<td><span class=\"status-badge " + statusClass + "\">" + escapeHtml(student.status) + "</span></td>" +
            "</tr>";
    }).join("");
}

function renderFollowupPreview(state) {
    var container = document.getElementById("followup-preview-list");

    if (!container) {
        return;
    }

    var followupStudents = state.students.filter(function (student) {
        return student.followUpReasons.length > 0;
    });

    container.innerHTML = followupStudents.map(function (student) {
        return "<article class=\"followup-preview-item\">" +
            "<h4>" + escapeHtml(student.name) + "</h4>" +
            "<p>" + escapeHtml(student.followUpReasons.join(" - ")) + "</p>" +
            "</article>";
    }).join("");
}

function renderWeeklyReport(state) {
    var container = document.getElementById("weekly-report-list");

    if (!container) {
        return;
    }

    container.innerHTML = state.students.map(function (student) {
        var totals = getStudentWeeklyTotals(student);

        return "<article class=\"weekly-student-card\" data-student-card data-student-id=\"" + student.id + "\">" +
            "<header class=\"weekly-student-head\">" +
            "<div><h3>" + escapeHtml(student.name) + "</h3><p>المستوى: " + escapeHtml(student.level) + "</p></div>" +
            "<button type=\"button\" class=\"btn-secondary add-batch-btn\" data-add-batch>+ إضافة دفعة تسميع</button>" +
            "</header>" +
            "<div class=\"weekly-student-body\">" +
            "<div class=\"table-wrap\">" +
            "<table class=\"weekly-table\">" +
            "<thead><tr><th>النوع</th><th>من</th><th>إلى</th><th>عدد الصفحات</th><th>ملاحظات</th><th>إجراء</th></tr></thead>" +
            "<tbody>" + renderStudentBatchRows(student) + "</tbody>" +
            "</table>" +
            "</div>" +
            "<div class=\"weekly-totals\">" +
            "<span class=\"total-pill\">مجموع صفحات الحفظ: <strong data-total-hifz>" + totals.hifz + "</strong></span>" +
            "<span class=\"total-pill\">مجموع صفحات المراجعة: <strong data-total-review>" + totals.review + "</strong></span>" +
            "<span class=\"total-pill\">المجموع الكلي: <strong data-total-all>" + totals.total + "</strong></span>" +
            "</div>" +
            "</div>" +
            "</article>";
    }).join("");
}

function renderStudentBatchRows(student) {
    if (!student.batches.length) {
        return "<tr><td colspan=\"6\"><button type=\"button\" class=\"btn-ghost add-batch-btn\" data-add-batch>+ إضافة أول دفعة لهذا الطالب</button></td></tr>";
    }

    return student.batches.map(function (batch, index) {
        var pages = calculateBatchPages(batch);

        return "<tr data-batch-row data-batch-index=\"" + index + "\">" +
            "<td><select data-field=\"type\"><option value=\"hifz\"" + (batch.type === "hifz" ? " selected" : "") + ">حفظ</option><option value=\"review\"" + (batch.type === "review" ? " selected" : "") + ">مراجعة</option></select></td>" +
            "<td><input type=\"number\" min=\"1\" data-field=\"from\" value=\"" + escapeHtml(String(batch.from)) + "\"></td>" +
            "<td><input type=\"number\" min=\"1\" data-field=\"to\" value=\"" + escapeHtml(String(batch.to)) + "\"></td>" +
            "<td><input type=\"number\" class=\"pages-field\" value=\"" + pages + "\" readonly></td>" +
            "<td><textarea rows=\"1\" data-field=\"notes\">" + escapeHtml(batch.notes || "") + "</textarea></td>" +
            "<td><button type=\"button\" class=\"btn-danger\" data-remove-batch>حذف</button></td>" +
            "</tr>";
    }).join("");
}

function initWeeklyReportInteractions(state) {
    var container = document.getElementById("weekly-report-list");

    if (!container) {
        return;
    }

    container.addEventListener("click", function (event) {
        var addButton = event.target.closest("[data-add-batch]");
        if (addButton) {
            var studentCardForAdd = addButton.closest("[data-student-card]");
            if (!studentCardForAdd) {
                return;
            }

            var studentForAdd = findStudentById(state.students, studentCardForAdd.getAttribute("data-student-id"));
            if (!studentForAdd) {
                return;
            }

            studentForAdd.batches.push({ type: "hifz", from: 1, to: 1, notes: "" });
            refreshStudentCard(studentCardForAdd, studentForAdd);
            refreshDependentSections(state);
            return;
        }

        var removeButton = event.target.closest("[data-remove-batch]");
        if (!removeButton) {
            return;
        }

        var studentCardForDelete = removeButton.closest("[data-student-card]");
        var batchRow = removeButton.closest("[data-batch-row]");

        if (!studentCardForDelete || !batchRow) {
            return;
        }

        var studentForDelete = findStudentById(state.students, studentCardForDelete.getAttribute("data-student-id"));
        if (!studentForDelete) {
            return;
        }

        var batchIndex = parseInt(batchRow.getAttribute("data-batch-index"), 10);
        if (!isNaN(batchIndex)) {
            studentForDelete.batches.splice(batchIndex, 1);
            refreshStudentCard(studentCardForDelete, studentForDelete);
            refreshDependentSections(state);
        }
    });

    container.addEventListener("input", function (event) {
        var changedField = event.target;
        if (!changedField.hasAttribute("data-field")) {
            return;
        }

        var studentCard = changedField.closest("[data-student-card]");
        var batchRow = changedField.closest("[data-batch-row]");
        if (!studentCard || !batchRow) {
            return;
        }

        var student = findStudentById(state.students, studentCard.getAttribute("data-student-id"));
        if (!student) {
            return;
        }

        var batchIndex = parseInt(batchRow.getAttribute("data-batch-index"), 10);
        var batch = student.batches[batchIndex];
        if (!batch) {
            return;
        }

        var fieldName = changedField.getAttribute("data-field");

        if (fieldName === "type") {
            batch.type = changedField.value;
        } else if (fieldName === "notes") {
            batch.notes = changedField.value;
        } else if (fieldName === "from" || fieldName === "to") {
            var numericValue = parseInt(changedField.value, 10);
            batch[fieldName] = isNaN(numericValue) ? 1 : numericValue;

            if (batch.to < batch.from) {
                batch.to = batch.from;
            }

            var fromInput = batchRow.querySelector("input[data-field='from']");
            var toInput = batchRow.querySelector("input[data-field='to']");
            if (fromInput) {
                fromInput.value = String(batch.from);
            }
            if (toInput) {
                toInput.value = String(batch.to);
            }
        }

        var pagesInput = batchRow.querySelector(".pages-field");
        if (pagesInput) {
            pagesInput.value = String(calculateBatchPages(batch));
        }

        refreshStudentTotals(studentCard, student);
        refreshDependentSections(state);
    });
}

function refreshStudentCard(studentCard, student) {
    var tbody = studentCard.querySelector("tbody");
    if (tbody) {
        tbody.innerHTML = renderStudentBatchRows(student);
    }

    refreshStudentTotals(studentCard, student);
}

function refreshStudentTotals(studentCard, student) {
    var totals = getStudentWeeklyTotals(student);

    var totalHifz = studentCard.querySelector("[data-total-hifz]");
    var totalReview = studentCard.querySelector("[data-total-review]");
    var totalAll = studentCard.querySelector("[data-total-all]");

    if (totalHifz) {
        totalHifz.textContent = String(totals.hifz);
    }
    if (totalReview) {
        totalReview.textContent = String(totals.review);
    }
    if (totalAll) {
        totalAll.textContent = String(totals.total);
    }
}

function refreshDependentSections(state) {
    renderSummaryStrip(state);
    renderQuickOverviewTable(state);
    renderStudentsPage(state.students);
    renderFollowupPreview(state);
    renderFollowupTable(state.students);
}

function renderWeeklyPerformance(weeklyPerformance) {
    var tableBody = document.getElementById("weekly-performance-body");
    var chartContainer = document.getElementById("weekly-performance-chart");

    if (tableBody) {
        tableBody.innerHTML = weeklyPerformance.map(function (item) {
            return "<tr>" +
                "<td>" + escapeHtml(item.week) + "</td>" +
                "<td>" + item.rate + "%</td>" +
                "<td>" + item.heard + "</td>" +
                "<td>" + item.pages + "</td>" +
                "</tr>";
        }).join("");
    }

    if (chartContainer) {
        chartContainer.innerHTML = weeklyPerformance.map(function (item) {
            return "<div class=\"simple-bar-row\">" +
                "<span>" + escapeHtml(item.week.replace("الأسبوع ", "أ")) + "</span>" +
                "<div class=\"simple-bar-track\"><i style=\"width: " + item.rate + "%;\"></i></div>" +
                "<strong>" + item.rate + "%</strong>" +
                "</div>";
        }).join("");
    }
}

function renderCumulativeStats(students) {
    var tbody = document.getElementById("cumulative-stats-body");

    if (!tbody) {
        return;
    }

    tbody.innerHTML = students.map(function (student) {
        return "<tr>" +
            "<td>" + escapeHtml(student.name) + "</td>" +
            "<td>" + student.cumulativeHifz + "</td>" +
            "<td>" + student.cumulativeReview + "</td>" +
            "<td>" + student.cumulativeSessions + "</td>" +
            "<td>" + student.activeWeeks + "</td>" +
            "<td>" + escapeHtml(student.lastActiveWeek) + "</td>" +
            "</tr>";
    }).join("");
}

function renderStudentsPage(students) {
    var tbody = document.getElementById("students-page-body");

    if (!tbody) {
        return;
    }

    tbody.innerHTML = students.map(function (student) {
        var statusClass = getStatusClass(student.status);

        return "<tr>" +
            "<td>" + escapeHtml(student.name) + "</td>" +
            "<td>" + escapeHtml(student.level) + "</td>" +
            "<td>" + escapeHtml(student.attendance) + "</td>" +
            "<td>" + escapeHtml(student.engagement) + "</td>" +
            "<td><a href=\"#weekly-report\" class=\"quick-link\">إدخال تسميع</a></td>" +
            "<td><span class=\"status-badge " + statusClass + "\">" + escapeHtml(student.status) + "</span></td>" +
            "</tr>";
    }).join("");
}

function renderFollowupTable(students) {
    var tbody = document.getElementById("followup-table-body");

    if (!tbody) {
        return;
    }

    var followupStudents = students.filter(function (student) {
        return student.followUpReasons.length > 0;
    });

    tbody.innerHTML = followupStudents.map(function (student) {
        return "<tr>" +
            "<td>" + escapeHtml(student.name) + "</td>" +
            "<td>" + escapeHtml(formatDateArabic(student.lastRecitationDate)) + "</td>" +
            "<td>" + student.followUpReasons.map(function (reason) {
                return "<span class=\"reason-badge status-alert\">" + escapeHtml(reason) + "</span>";
            }).join("") + "</td>" +
            "<td><span class=\"status-badge " + getStatusClass(student.status) + "\">" + escapeHtml(student.status) + "</span></td>" +
            "<td>" + escapeHtml(student.actionSuggestion) + "</td>" +
            "</tr>";
    }).join("");
}

function initSectionNavigation() {
    var sectionLinks = Array.prototype.slice.call(document.querySelectorAll("[data-section-link]"));
    var sections = Array.prototype.slice.call(document.querySelectorAll("[data-section]"));

    if (!sectionLinks.length || !sections.length) {
        return;
    }

    sectionLinks.forEach(function (link) {
        link.addEventListener("click", function () {
            sectionLinks.forEach(function (item) {
                item.classList.remove("active");
            });
            link.classList.add("active");
        });
    });

    if (typeof IntersectionObserver !== "function") {
        return;
    }

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) {
                return;
            }

            var visibleId = entry.target.getAttribute("id");
            sectionLinks.forEach(function (link) {
                var linkTarget = (link.getAttribute("href") || "").replace("#", "");
                link.classList.toggle("active", linkTarget === visibleId);
            });
        });
    }, {
        threshold: 0.45
    });

    sections.forEach(function (section) {
        observer.observe(section);
    });
}

function findStudentById(students, id) {
    var numericId = parseInt(id, 10);
    return students.find(function (student) {
        return student.id === numericId;
    });
}

function getStudentWeeklyTotals(student) {
    return student.batches.reduce(function (totals, batch) {
        var pages = calculateBatchPages(batch);

        if (batch.type === "hifz") {
            totals.hifz += pages;
        } else {
            totals.review += pages;
        }

        totals.total += pages;
        return totals;
    }, { hifz: 0, review: 0, total: 0 });
}

function getLatestBatch(student) {
    if (!student.batches.length) {
        return null;
    }

    return student.batches[student.batches.length - 1];
}

function calculateBatchPages(batch) {
    var from = parseInt(batch.from, 10);
    var to = parseInt(batch.to, 10);

    if (isNaN(from) || isNaN(to)) {
        return 0;
    }

    if (to < from) {
        return 1;
    }

    return to - from + 1;
}

function batchTypeLabel(type) {
    return type === "hifz" ? "حفظ" : "مراجعة";
}

function getStatusClass(statusText) {
    if (statusText === "جيد") {
        return "status-good";
    }

    if (statusText === "متابعة") {
        return "status-mid";
    }

    return "status-alert";
}

function formatDateArabic(isoDate) {
    if (!isoDate) {
        return "-";
    }

    var date = new Date(isoDate);

    if (isNaN(date.getTime())) {
        return isoDate;
    }

    return date.toLocaleDateString("ar-EG", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit"
    });
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}



