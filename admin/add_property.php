<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Add Property | 360HomesHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <aside></aside>
    <main>
        <header style="margin-bottom: 2rem;">
            <h1>Add Property for Host</h1>
            <p id="host-info" style="color: var(--text-dim)">Loading host info...</p>
        </header>

        <div class="content-card" style="max-width: 800px;">
            <form id="add-property-form">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label>Property Name</label>
                        <input type="text" id="name" placeholder="Luxury Studio" required>
                    </div>
                    <div>
                        <label>Property Type</label>
                        <select id="type">
                            <option value="apartment">Apartment</option>
                            <option value="house">House</option>
                            <option value="studio">Studio</option>
                            <option value="duplex">Duplex</option>
                            <option value="hotel">Hotel</option>
                        </select>
                    </div>
                </div>

                <label>Address</label>
                <input type="text" id="address" placeholder="123 Main St" required>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                    <div>
                        <label>City</label>
                        <input type="text" id="city" required>
                    </div>
                    <div>
                        <label>State</label>
                        <input type="text" id="state" required>
                    </div>
                    <div>
                        <label>Country</label>
                        <input type="text" id="country" value="Nigeria" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                    <div>
                        <label>Price (₦)</label>
                        <input type="number" id="price" required>
                    </div>
                    <div>
                        <label>Max Guests</label>
                        <input type="number" id="guests_max" value="1" required>
                    </div>
                    <div>
                        <label>Bedrooms</label>
                        <input type="number" id="bedrooms" value="1" required>
                    </div>
                </div>

                <label>Description</label>
                <textarea id="description" rows="4" placeholder="Describe the property..."></textarea>

                <div style="margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Create & Publish Property</button>
                    <p id="msg" style="margin-top: 1rem; text-align: center; font-size: 0.875rem;"></p>
                </div>
            </form>
        </div>
    </main>

    <script>
        const token = localStorage.getItem('jwt_token');
        if (!token) window.location.href = 'login.php';

        const params = new URLSearchParams(window.location.search);
        const hostId = params.get('host_id');

        if (!hostId) {
            alert('Host ID is required');
            window.location.href = 'users.php';
        }

        document.getElementById('host-info').textContent = `Assigning property to Host ID: ${hostId}`;

        document.getElementById('add-property-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const msg = document.getElementById('msg');
            msg.textContent = 'Creating property...';

            const data = {
                host_id: hostId,
                name: document.getElementById('name').value,
                type: document.getElementById('type').value,
                address: document.getElementById('address').value,
                city: document.getElementById('city').value,
                state: document.getElementById('state').value,
                country: document.getElementById('country').value,
                price: document.getElementById('price').value,
                guests_max: document.getElementById('guests_max').value,
                bedrooms: document.getElementById('bedrooms').value,
                description: document.getElementById('description').value
            };

            try {
                const res = await fetch('../api/admin/create_property.php', {
                    method: 'POST',
                    headers: { 
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                const response = await res.json();
                if (response.success) {
                    msg.style.color = 'var(--success)';
                    msg.textContent = 'Property created and published successfully!';
                    setTimeout(() => window.location.href = 'index.php', 1500);
                } else {
                    msg.style.color = 'var(--danger)';
                    msg.textContent = response.message;
                }
            } catch (err) {
                msg.textContent = 'Error: ' + err.message;
            }
        });
    </script>
    <script src="js/sidebar.js"></script>
</body>
</html>
