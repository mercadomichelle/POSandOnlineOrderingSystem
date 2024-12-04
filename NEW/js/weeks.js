
function generateWeeks(year, month) {
    const weeks = [];
    const firstDay = new Date(year, month - 1, 1); // First day of the month
    const lastDay = new Date(year, month, 0); // Last day of the month
    let currentWeek = [];

    for (let date = firstDay; date <= lastDay; date.setDate(date.getDate() + 1)) {
        if (date.getDay() === 0 && currentWeek.length > 0) {
            // If it's Sunday (week start), push the previous week and start a new one
            weeks.push(currentWeek);
            currentWeek = [];
        }
        currentWeek.push(new Date(date)); // Add current date to the week
    }
    if (currentWeek.length > 0) {
        // Push the last week if there are remaining dates
        weeks.push(currentWeek);
    }
    return weeks;
}

function updateWeekSelector() {
    const month = parseInt(document.getElementById("monthSelector").value);
    const year = parseInt(document.getElementById("yearSelector").value);
    const weekSelector = document.getElementById("weekSelector");

    // Clear previous options
    weekSelector.innerHTML = `<option value="">Select Week</option>`;

    if (!month || !year) return; // Exit if month or year is not selected

    // Generate weeks for the selected month and year
    const weeks = generateWeeks(year, month);
    weeks.forEach((week, index) => {
        const start = week[0].toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric'
        });
        const end = week[week.length - 1].toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric'
        });
        // weekSelector.innerHTML += `<option value="${index + 1}">Week ${index + 1} (${start} - ${end})</option>`;
        weekSelector.innerHTML += `<option value="${index + 1}">(${start} - ${end})</option>`;
    });
}

// Set default values and update week selector
const now = new Date();
document.getElementById("monthSelector").value = now.getMonth() + 1; // Current month
document.getElementById("yearSelector").value = now.getFullYear(); // Current year

updateWeekSelector(); // Initialize weeks

// Event listeners for dynamic updates
document.getElementById("monthSelector").addEventListener("change", updateWeekSelector);
document.getElementById("yearSelector").addEventListener("change", updateWeekSelector);
