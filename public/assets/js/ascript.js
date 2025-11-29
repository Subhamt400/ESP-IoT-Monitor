document.addEventListener('DOMContentLoaded', function () {
    const sensorTable = document.getElementById('sensor-table').querySelector('tbody');
    const addSensorButton = document.getElementById('add-sensor');
    const logoutButton = document.getElementById('logout-button');
    const localDashboardButton = document.getElementById('local-dashboard-button');
    const adminNameSpan = document.getElementById('admin-name');

    const apiBase = '../server/admin_dashboard.php';

    async function fetchAdminName() {
        try {
            const response = await fetch(`${apiBase}?action=get_admin_name`);
            if (!response.ok) return;
            const data = await response.json();
            if (data.admin_name) {
                adminNameSpan.textContent = data.admin_name;
            }
        } catch (error) {
            console.error('Error fetching admin name:', error);
        }
    }

    fetchAdminName();

    async function fetchSensorData() {
        try {
            const response = await fetch(`${apiBase}?action=fetch`);
            const data = await response.json();
            sensorTable.innerHTML = '';

            data.forEach(sensor => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${sensor.id}</td>
                    <td contenteditable="true">${sensor.lab_name}</td>
                    <td contenteditable="true">${sensor.esp8266_id}</td>
                    <td contenteditable="true">${sensor.sensor_short_name}</td>
                    <td contenteditable="true">${sensor.data_interval}</td>
                    <td>
                        <button class="save" data-id="${sensor.id}">Save</button>
                        <button class="delete" data-id="${sensor.id}">Delete</button>
                    </td>
                `;
                sensorTable.appendChild(row);
            });
        } catch (error) {
            console.error('Error fetching sensor data:', error);
        }
    }

    sensorTable.addEventListener('click', async function (event) {
        if (event.target.classList.contains('save')) {
            const row = event.target.closest('tr');
            const id = parseInt(event.target.dataset.id, 10);
            const updatedData = {
                id: id,
                lab_name: row.children[1].textContent.trim(),
                esp8266_id: parseInt(row.children[2].textContent.trim(), 10) || 0,
                sensor_short_name: row.children[3].textContent.trim(),
                data_interval: parseInt(row.children[4].textContent.trim(), 10) || 60
            };

            try {
                const response = await fetch(`${apiBase}?action=update`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(updatedData),
                });
                const result = await response.json();
                alert(result.message || 'Data updated successfully');
            } catch (error) {
                console.error('Error updating sensor data:', error);
            }
        }

        if (event.target.classList.contains('delete')) {
            const id = parseInt(event.target.dataset.id, 10);

            try {
                const response = await fetch(`${apiBase}?action=delete&id=${id}`, { method: 'GET' });
                const result = await response.json();
                alert(result.message || 'Sensor deleted successfully');
                fetchSensorData();
            } catch (error) {
                console.error('Error deleting sensor data:', error);
            }
        }
    });

    addSensorButton.addEventListener('click', async function () {
        const generatedId = Date.now();
        const newSensor = {
            lab_name: 'New Lab',
            esp8266_id: generatedId > 0 ? generatedId : Math.floor(Math.random() * 1000),
            sensor_short_name: 'New Sensor',
            data_interval: 60
        };

        try {
            const response = await fetch(`${apiBase}?action=add`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(newSensor),
            });
            const result = await response.json();

            if (result.message) {
                alert(result.message);
                fetchSensorData();
            }
        } catch (error) {
            console.error('Error adding sensor:', error);
        }
    });

    localDashboardButton.addEventListener('click', function () {
        window.location.href = 'local_dashboard.html';
    });

    logoutButton.addEventListener('click', async function () {
        try {
            const response = await fetch('../server/logout.php', { method: 'GET' });
            if (response.redirected) {
                window.location.href = 'login.html';
            } else {
                // Fallback: redirect to login page
                window.location.href = 'login.html';
            }
        } catch (error) {
            console.error('Error during logout:', error);
            window.location.href = 'login.html';
        }
    });

    fetchSensorData();
});
