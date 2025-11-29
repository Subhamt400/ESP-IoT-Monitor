document.addEventListener('DOMContentLoaded', fetchLabs);

async function fetchLabs() {
    try {
        const response = await fetch('../server/local.php?labs=true');
        const labs = await response.json();

        if (!Array.isArray(labs) || labs.length === 0) {
            console.error("No labs found or invalid response:", labs);
            return;
        }

        const labDropdown = document.getElementById("lab-name");
        labDropdown.innerHTML = `<option value="">-- Select Lab --</option>`;

        labs.forEach(lab => {
            const option = document.createElement("option");
            option.value = lab.lab_name;
            option.textContent = lab.lab_name;
            labDropdown.appendChild(option);
        });
    } catch (error) {
        console.error('Error fetching lab names:', error);
    }
}

async function fetchLabDevices() {
    const labName = document.getElementById('lab-name').value;
    if (!labName) {
        document.getElementById('device-selector').style.display = 'none';
        return;
    }

    try {
        const response = await fetch(`../server/local.php?lab=${encodeURIComponent(labName)}`);
        const devices = await response.json();
        const deviceDropdown = document.getElementById('esp8266-id');
        deviceDropdown.innerHTML = `<option value="all">-- All Devices --</option>`;
        devices.forEach(device => {
            const option = document.createElement('option');
            option.value = device.esp8266_id;
            option.textContent = device.esp8266_id;
            deviceDropdown.appendChild(option);
        });
        document.getElementById('device-selector').style.display = 'block';
    } catch (error) {
        console.error('Error fetching devices:', error);
    }
}

async function fetchRecords() {
    const esp8266Id = document.getElementById('esp8266-id').value;
    const fromDate = document.getElementById('from-date').value;
    const toDate = document.getElementById('to-date').value;

    if (!fromDate || !toDate) {
        alert('Please fill all required fields.');
        return;
    }

    if (new Date(toDate) < new Date(fromDate)) {
        alert('Invalid date range. "To" date must be after "From" date.');
        return;
    }

    let deviceIds = [esp8266Id];
    if (esp8266Id === 'all') {
        const labName = document.getElementById('lab-name').value;
        if (!labName) { alert("Please select a lab first."); return; }
        deviceIds = await getDeviceIdsForLab(labName);
    }

    const url = `../server/local.php?deviceIds=${encodeURIComponent(JSON.stringify(deviceIds))}&fromDate=${fromDate}&toDate=${toDate}`;

    try {
        const response = await fetch(url);
        const data = await response.json();
        console.log("Fetched data:", data);
        if (data.error) {
            document.getElementById('data-table').innerHTML = `<p>${data.error}</p>`;
            document.getElementById('download-pdf').style.display = 'none';
            return;
        }

        if (!Array.isArray(data) || data.length === 0) {
            document.getElementById('data-table').innerHTML = `<p>No records found for the selected criteria.</p>`;
            document.getElementById('download-pdf').style.display = 'none';
            return;
        }

        let table = '<table><tr><th>Device ID</th><th>Temperature</th><th>Humidity</th><th>Recorded At</th></tr>';
        data.forEach(record => {
            table += `<tr>
                <td>${record.esp8266_id}</td>
                <td>${record.temperature}</td>
                <td>${record.humidity}</td>
                <td>${record.recorded_at}</td>
            </tr>`;
        });
        table += '</table>';

        document.getElementById('data-table').innerHTML = table;
        document.getElementById('download-pdf').style.display = 'block';
    } catch (error) {
        document.getElementById('data-table').innerHTML = `<p>Error fetching records: ${error.message}</p>`;
        document.getElementById('download-pdf').style.display = 'none';
    }
}

async function getDeviceIdsForLab(labName) {
    try {
        const response = await fetch(`../server/local.php?lab=${encodeURIComponent(labName)}`);
        const devices = await response.json();
        return devices.map(device => device.esp8266_id);
    } catch (error) {
        console.error('Error fetching devices:', error);
        return [];
    }
}

function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    const table = document.querySelector('table');
    if (!table) { alert('No data to download.'); return; }
    const labName = document.getElementById('lab-name').value;
    let data = [];
    const headers = Array.from(table.querySelectorAll('th')).map(th => th.innerText);
    data.push(headers);
    const rows = table.querySelectorAll('tr');
    rows.forEach((row, index) => {
        if (index > 0) {
            const cells = Array.from(row.querySelectorAll('td')).map(td => td.innerText);
            data.push(cells);
        }
    });
    doc.text('Electro Meter Corporation, Kolkata - Labs', 10, 10);
    doc.text(`Temperature & Relative Humidity (${labName})`, 10, 20);
    doc.autoTable({ head: [headers], body: data.slice(1), startY: 30 });
    doc.save('Sensor_Data.pdf');
}

function logout() {
    fetch('../server/logout.php')
        .then(response => { window.location.href = '../public/login.html'; })
        .catch(error => console.error('Logout failed:', error));
}
