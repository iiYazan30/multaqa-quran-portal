document.addEventListener("DOMContentLoaded", function () {
	initStudentsRangeToggle();
	initWeeklyRecitationCalculator();
});

function initStudentsRangeToggle() {
	var rangeButtons = document.querySelectorAll(".report-range .range-btn[data-range]");
	var reportBlocks = document.querySelectorAll(".report-block[data-report]");

	if (!rangeButtons.length || !reportBlocks.length) {
		return;
	}

	function setActiveRange(targetRange) {
		rangeButtons.forEach(function (button) {
			var isActive = button.getAttribute("data-range") === targetRange;
			button.classList.toggle("active", isActive);
			button.setAttribute("aria-pressed", isActive ? "true" : "false");
		});

		reportBlocks.forEach(function (block) {
			var shouldShow = block.getAttribute("data-report") === targetRange;
			block.hidden = !shouldShow;
		});
	}

	rangeButtons.forEach(function (button) {
		button.addEventListener("click", function () {
			setActiveRange(button.getAttribute("data-range"));
		});
	});
}

function initWeeklyRecitationCalculator() {
	var typeInput = document.getElementById("recitation-type");
	var fromInput = document.getElementById("page-from");
	var toInput = document.getElementById("page-to");
	var pagesInput = document.getElementById("total-pages");
	var pointsInput = document.getElementById("total-points");

	if (!typeInput || !fromInput || !toInput || !pagesInput || !pointsInput) {
		return;
	}

	function calculateTotals() {
		var fromPage = parseInt(fromInput.value, 10);
		var toPage = parseInt(toInput.value, 10);

		if (isNaN(fromPage) || isNaN(toPage)) {
			pagesInput.value = "0";
			pointsInput.value = "0";
			return;
		}

		if (toPage < fromPage) {
			toPage = fromPage;
			toInput.value = String(fromPage);
		}

		var totalPages = toPage - fromPage + 1;
		var multiplier = typeInput.value === "hifz" ? 5 : 1;
		var totalPoints = totalPages * multiplier;

		pagesInput.value = String(totalPages);
		pointsInput.value = String(totalPoints);
	}

	typeInput.addEventListener("change", calculateTotals);
	fromInput.addEventListener("input", calculateTotals);
	toInput.addEventListener("input", calculateTotals);

	calculateTotals();
}

