<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>360HomeSub API Documentation</title>
    <link rel="stylesheet" href="https://unpkg.com/sakura.css/css/sakura.css" type="text/css">
    <style>
        body {
            display: flex;
            font-family: sans-serif;
        }
        #sidebar {
            width: 250px;
            padding: 20px;
            border-right: 1px solid #ddd;
            height: 100vh;
            overflow-y: auto;
            position: fixed;
        }
        #main-content {
            padding: 20px;
            margin-left: 270px;
            width: calc(100% - 270px);
        }
        .endpoint {
            margin-bottom: 40px;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
        }
        .endpoint h3 {
            margin-top: 0;
        }
        .endpoint pre {
            background-color: #f4f4f4;
            padding: 10px;
            border-radius: 5px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .endpoint button {
            margin-top: 10px;
        }
        .method {
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
        }
        .POST { background-color: #4CAF50; }
        .GET { background-color: #2196F3; }
    </style>
</head>
<body>
    <div id="sidebar">
        <h2>API Endpoints</h2>
        <ul>
            <li><a href="#auth">Auth</a></li>
            <li><a href="#onboarding">Onboarding</a></li>
            <li><a href="#kyc">KYC</a></li>
            <li><a href="#dashboard">Dashboard</a></li>
            <li><a href="#properties">Properties</a></li>
            <li><a href="#admin">Admin</a></li>
        </ul>
        <hr>
        <label for="jwt_token">JWT Token:</label>
        <textarea id="jwt_token" rows="5" style="width: 100%;"></textarea>
    </div>
    <div id="main-content">
        <h1>360HomeSub API Playground</h1>
        <p>Test the API endpoints directly from your browser. Enter your JWT token in the sidebar to test authenticated endpoints.</p>

        <section id="auth">
            <h2>Auth</h2>
            <div class="endpoint">
                <h3><span class="method POST">POST</span> /api/auth/register_email.php</h3>
                <p>Registers a new user with their email and password.</p>
                <form id="register_email_form">
                    <label for="register_email_email">Email:</label>
                    <input type="email" id="register_email_email" required>
                    <label for="register_email_password">Password:</label>
                    <input type="password" id="register_email_password" required>
                    <button type="submit">Send Request</button>
                </form>
                <pre id="register_email_response"></pre>
            </div>
            <div class="endpoint">
                <h3><span class="method POST">POST</span> /api/auth/register_phone.php</h3>
                <p>Registers a new user with their phone number and password.</p>
                <form id="register_phone_form">
                    <label for="register_phone_phone">Phone:</label>
                    <input type="text" id="register_phone_phone" required>
                    <label for="register_phone_password">Password:</label>
                    <input type="password" id="register_phone_password" required>
                    <button type="submit">Send Request</button>
                </form>
                <pre id="register_phone_response"></pre>
            </div>
            <div class="endpoint">
                <h3><span class="method POST">POST</span> /api/auth/verify_otp.php</h3>
                <p>Verifies the OTP sent to the user.</p>
                <form id="verify_otp_form">
                    <label for="verify_otp_user_id">User ID:</label>
                    <input type="number" id="verify_otp_user_id" required>
                    <label for="verify_otp_code">OTP Code:</label>
                    <input type="text" id="verify_otp_code" required>
                    <button type="submit">Send Request</button>
                </form>
                <pre id="verify_otp_response"></pre>
            </div>
            <div class="endpoint">
                <h3><span class="method POST">POST</span> /api/auth/login.php</h3>
                <p>Logs in a user with their email/phone and password.</p>
                <form id="login_form">
                    <label for="login_email_phone">Email or Phone:</label>
                    <input type="text" id="login_email_phone" required>
                    <label for="login_password">Password:</label>
                    <input type="password" id="login_password" required>
                    <button type="submit">Send Request</button>
                </form>
                <pre id="login_response"></pre>
            </div>
            <div class="endpoint">
                <h3><span class="method POST">POST</span> /api/auth/google_auth.php</h3>
                <p>Authenticates a user with a Google ID token.</p>
                <form id="google_auth_form">
                    <label for="google_auth_id_token">ID Token:</label>
                    <input type="text" id="google_auth_id_token" required>
                    <button type="submit">Send Request</button>
                </form>
                <pre id="google_auth_response"></pre>
            </div>
            <div class="endpoint">
                <h3><span class="method POST">POST</span> /api/auth/set_password.php</h3>
                <p>Sets a user's password (for Google registration or password change).</p>
                <form id="set_password_form">
                    <label for="set_password_password">New Password:</label>
                    <input type="password" id="set_password_password" required>
                    <button type="submit">Send Request</button>
                </form>
                <pre id="set_password_response"></pre>
            </div>
        </section>

        <section id="onboarding">
            <h2>Onboarding</h2>
            <div class="endpoint">
                <h3><span class="method POST">POST</span> /api/onboarding/set_profile.php</h3>
                <p>Sets the user's profile information.</p>
                <form id="set_profile_form">
                    <label for="set_profile_first_name">First Name:</label>
                    <input type="text" id="set_profile_first_name" required>
                    <label for="set_profile_last_name">Last Name:</label>
                    <input type="text" id="set_profile_last_name" required>
                    <label for="set_profile_bio">Bio:</label>
                    <textarea id="set_profile_bio"></textarea>
                    <button type="submit">Send Request</button>
                </form>
                <pre id="set_profile_response"></pre>
            </div>
            <div class="endpoint">
                <h3><span class="method POST">POST</span> /api/onboarding/set_location.php</h3>
                <p>Sets the user's location.</p>
                <form id="set_location_form">
                    <label for="set_location_address">Address:</label>
                    <input type="text" id="set_location_address" required>
                    <label for="set_location_city">City:</label>
                    <input type="text" id="set_location_city" required>
                    <label for="set_location_state">State:</label>
                    <input type="text" id="set_location_state" required>
                    <label for="set_location_country">Country:</label>
                    <input type="text" id="set_location_country" required>
                    <button type="submit">Send Request</button>
                </form>
                <pre id="set_location_response"></pre>
            </div>
            <div class="endpoint">
                <h3><span class="method POST">POST</span> /api/onboarding/upload_avatar.php</h3>
                <p>Uploads a user's avatar.</p>
                <form id="upload_avatar_form">
                    <label for="upload_avatar_avatar">Avatar:</label>
                    <input type="file" id="upload_avatar_avatar" required>
                    <button type="submit">Send Request</button>
                </form>
                <pre id="upload_avatar_response"></pre>
            </div>
            <div class="endpoint">
                <h3><span class="method POST">POST</span> /api/onboarding/set_role.php</h3>
                <p>Sets the user's role.</p>
                <form id="set_role_form">
                    <label for="set_role_role">Role:</label>
                    <select id="set_role_role">
                        <option value="guest">Guest</option>
                        <option value="host">Host</option>
                    </select>
                    <button type="submit">Send Request</button>
                </form>
                <pre id="set_role_response"></pre>
            </div>
        </section>

        <section id="kyc">
            <h2>KYC</h2>
            <div class="endpoint">
                <h3><span class="method GET">GET</span> /api/kyc/kyc_status.php</h3>
                <p>Retrieves the user's KYC status.</p>
                <form id="kyc_status_form">
                    <button type="submit">Send Request</button>
                </form>
                <pre id="kyc_status_response"></pre>
            </div>
            <div class="endpoint">
                <h3><span class="method POST">POST</span> /api/kyc/start_kyc.php</h3>
                <p>Starts the KYC process.</p>
                <form id="start_kyc_form">
                    <label for="start_kyc_country">Country:</label>
                    <input type="text" id="start_kyc_country" required>
                    <label for="start_kyc_identity_type">Identity Type:</label>
                    <select id="start_kyc_identity_type">
                        <option value="passport">Passport</option>
                        <option value="national_id">National ID</option>
                        <option value="drivers_license">Driver's License</option>
                    </select>
                    <button type="submit">Send Request</button>
                </form>
                <pre id="start_kyc_response"></pre>
            </div>
            <div class="endpoint">
                <h3><span class="method POST">POST</span> /api/kyc/upload_documents.php</h3>
                <p>Uploads KYC documents.</p>
                <form id="upload_documents_form">
                    <label for="upload_documents_country">Country:</label>
                    <input type="text" id="upload_documents_country" required>
                    <label for="upload_documents_identity_type">Identity Type:</label>
                    <select id="upload_documents_identity_type">
                        <option value="passport">Passport</option>
                        <option value="national_id">National ID</option>
                        <option value="drivers_license">Driver's License</option>
                    </select>
                    <label for="upload_documents_id_front">ID Front:</label>
                    <input type="file" id="upload_documents_id_front" required>
                    <label for="upload_documents_id_back">ID Back:</label>
                    <input type="file" id="upload_documents_id_back" required>
                    <button type="submit">Send Request</button>
                </form>
                <pre id="upload_documents_response"></pre>
            </div>
            <div class="endpoint">
                <h3><span class="method POST">POST</span> /api/kyc/upload_selfie.php</h3>
                <p>Uploads a selfie for KYC.</p>
                <form id="upload_selfie_form">
                    <label for="upload_selfie_selfie">Selfie:</label>
                    <input type="file" id="upload_selfie_selfie" required>
                    <button type="submit">Send Request</button>
                </form>
                <pre id="upload_selfie_response"></pre>
            </div>
        </section>
        
        <section id="dashboard">
            <h2>Dashboard</h2>
            <div class="endpoint">
                <h3><span class="method POST">POST</span> /api/dashboard/home.php</h3>
                <p>Retrieves dashboard data.</p>
                <form id="dashboard_home_form">
                    <label for="dashboard_home_latitude">Latitude:</label>
                    <input type="number" step="any" id="dashboard_home_latitude" required>
                    <label for="dashboard_home_longitude">Longitude:</label>
                    <input type="number" step="any" id="dashboard_home_longitude" required>
                    <button type="submit">Send Request</button>
                </form>
                <pre id="dashboard_home_response"></pre>
            </div>
        </section>
        
        <section id="properties">
            <h2>Properties</h2>
            <div class="endpoint">
                <h3><span class="method POST">POST</span> /api/properties/list.php</h3>
                <p>Lists properties.</p>
                <form id="properties_list_form">
                    <label for="properties_list_latitude">Latitude:</label>
                    <input type="number" step="any" id="properties_list_latitude" required>
                    <label for="properties_list_longitude">Longitude:</label>
                    <input type="number" step="any" id="properties_list_longitude" required>
                    <label for="properties_list_page">Page:</label>
                    <input type="number" id="properties_list_page" value="1">
                    <button type="submit">Send Request</button>
                </form>
                <pre id="properties_list_response"></pre>
            </div>
            <div class="endpoint">
                <h3><span class="method POST">POST</span> /api/properties/search.php</h3>
                <p>Searches properties.</p>
                <form id="properties_search_form">
                    <label for="properties_search_latitude">Latitude:</label>
                    <input type="number" step="any" id="properties_search_latitude" required>
                    <label for="properties_search_longitude">Longitude:</label>
                    <input type="number" step="any" id="properties_search_longitude" required>
                    <label for="properties_search_keyword">Keyword:</label>
                    <input type="text" id="properties_search_keyword">
                    <label for="properties_search_type">Type:</label>
                    <input type="text" id="properties_search_type">
                    <label for="properties_search_price_min">Min Price:</label>
                    <input type="number" id="properties_search_price_min">
                    <label for="properties_search_price_max">Max Price:</label>
                    <input type="number" id="properties_search_price_max">
                    <button type="submit">Send Request</button>
                </form>
                <pre id="properties_search_response"></pre>
            </div>
            <div class="endpoint">
                <h3><span class="method POST">POST</span> /api/properties/view.php</h3>
                <p>Views a property.</p>
                <form id="properties_view_form">
                    <label for="properties_view_property_id">Property ID:</label>
                    <input type="number" id="properties_view_property_id" required>
                    <label for="properties_view_latitude">Latitude:</label>
                    <input type="number" step="any" id="properties_view_latitude" required>
                    <label for="properties_view_longitude">Longitude:</label>
                    <input type="number" step="any" id="properties_view_longitude" required>
                    <button type="submit">Send Request</button>
                </form>
                <pre id="properties_view_response"></pre>
            </div>
        </section>

        <section id="admin">
            <h2>Admin</h2>
            <div class="endpoint">
                <h3><span class="method POST">POST</span> /api/admin/login.php</h3>
                <p>Logs in an admin.</p>
                <form id="admin_login_form">
                    <label for="admin_login_email">Email:</label>
                    <input type="email" id="admin_login_email" required>
                    <label for="admin_login_password">Password:</label>
                    <input type="password" id="admin_login_password" required>
                    <button type="submit">Send Request</button>
                </form>
                <pre id="admin_login_response"></pre>
            </div>
            <div class="endpoint">
                <h3><span class="method GET">GET</span> /api/admin/kyc_list.php</h3>
                <p>Lists KYC applications.</p>
                <form id="admin_kyc_list_form">
                    <label for="admin_kyc_list_status">Status:</label>
                    <select id="admin_kyc_list_status">
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <button type="submit">Send Request</button>
                </form>
                <pre id="admin_kyc_list_response"></pre>
            </div>
            <div class="endpoint">
                <h3><span class="method POST">POST</span> /api/admin/approve_kyc.php</h3>
                <p>Approves a KYC application.</p>
                <form id="admin_approve_kyc_form">
                    <label for="admin_approve_kyc_id">KYC ID:</label>
                    <input type="number" id="admin_approve_kyc_id" required>
                    <button type="submit">Send Request</button>
                </form>
                <pre id="admin_approve_kyc_response"></pre>
            </div>
            <div class="endpoint">
                <h3><span class="method POST">POST</span> /api/admin/reject_kyc.php</h3>
                <p>Rejects a KYC application.</p>
                <form id="admin_reject_kyc_form">
                    <label for="admin_reject_kyc_id">KYC ID:</label>
                    <input type="number" id="admin_reject_kyc_id" required>
                    <label for="admin_reject_kyc_note">Admin Note:</label>
                    <textarea id="admin_reject_kyc_note"></textarea>
                    <button type="submit">Send Request</button>
                </form>
                <pre id="admin_reject_kyc_response"></pre>
            </div>
        </section>
        
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formId = form.id;
                    const responseElement = document.getElementById(formId.replace('_form', '_response'));
                    const endpoint = '/api/' + formId.replace(/_/g, '/').replace('/form', '.php');
                    const method = form.parentElement.querySelector('.method').textContent;

                    let headers = { 'Content-Type': 'application/json' };
                    const jwtToken = document.getElementById('jwt_token').value;
                    if (jwtToken) {
                        headers['Authorization'] = 'Bearer ' + jwtToken;
                    }

                    let body;
                    const inputs = form.querySelectorAll('input, select, textarea');
                    
                    if (form.enctype === 'multipart/form-data') {
                        headers = {}; // Let browser set content type for multipart
                         if (jwtToken) {
                            headers['Authorization'] = 'Bearer ' + jwtToken;
                        }
                        body = new FormData();
                        inputs.forEach(input => {
                            if (input.type === 'file') {
                                body.append(input.id.split('_').pop(), input.files[0]);
                            } else {
                                body.append(input.id.split('_').pop(), input.value);
                            }
                        });
                    } else {
                        const data = {};
                        inputs.forEach(input => {
                           if(input.type !== 'submit'){
                                let key = input.id.substring(input.id.lastIndexOf('_') + 1);
                                if (formId === 'login_form' && key === 'phone') {
                                    key = 'email_phone';
                                }
                                data[key] = input.value;
                           }
                        });

                        if (formId === 'login_form') {
                            const loginInput = document.getElementById('login_email_phone').value;
                            if (loginInput.includes('@')) {
                                data.email = loginInput;
                            } else {
                                data.phone = loginInput;
                            }
                            delete data.email_phone;
                        }

                        body = JSON.stringify(data);
                    }
                    
                    let url = '../' + endpoint;
                    if (method === 'GET' && formId === 'admin_kyc_list_form') {
                        const status = document.getElementById('admin_kyc_list_status').value;
                        if (status) {
                            url += '?status=' + status;
                        }
                    }

                    try {
                        const response = await fetch(url, {
                            method: method,
                            headers: headers,
                            body: method === 'GET' ? null : body
                        });
                        const responseData = await response.json();
                        responseElement.textContent = JSON.stringify(responseData, null, 2);
                        
                        if(responseData.data && responseData.data.token) {
                            document.getElementById('jwt_token').value = responseData.data.token;
                        }

                    } catch (error) {
                        responseElement.textContent = 'Error: ' + error.message;
                    }
                });
            });
            
            // Set correct enctype for file uploads
            document.getElementById('upload_avatar_form').enctype = 'multipart/form-data';
            document.getElementById('upload_documents_form').enctype = 'multipart/form-data';
            document.getElementById('upload_selfie_form').enctype = 'multipart/form-data';

        });
    </script>
</body>
</html>
