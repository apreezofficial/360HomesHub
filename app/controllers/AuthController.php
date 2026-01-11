<?php
require_once __DIR__ . '/ApiController.php';
use \Firebase\JWT\JWT;

class AuthController extends ApiController {
    private $userModel;
    private $otpModel;
    private $temporaryUserModel;

    public function __construct() {
        $this->userModel = $this->model('User');
        $this->otpModel = $this->model('Otp');
        $this->temporaryUserModel = $this->model('TemporaryUser');
    }

    public function createPassword() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'email' => isset($_POST['email']) ? trim($_POST['email']) : null,
                'phone' => isset($_POST['phone']) ? trim($_POST['phone']) : null,
                'password' => trim($_POST['password']),
                'password_err' => ''
            ];

            if (empty($data['password'])) {
                $this->sendJsonResponse(['error' => 'Password is required'], 400);
            } elseif (strlen($data['password']) < 6) {
                $this->sendJsonResponse(['error' => 'Password must be at least 6 characters'], 400);
            }

            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

            if ($this->temporaryUserModel->createTemporaryUser($data)) {
                $this->sendJsonResponse(['message' => 'Password created successfully']);
            } else {
                $this->sendJsonResponse(['error' => 'Failed to create password'], 500);
            }
        } else {
            $this->sendJsonResponse(['error' => 'Invalid request method'], 405);
        }
    }

    public function collectPersonalDetails() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'email' => isset($_POST['email']) ? trim($_POST['email']) : null,
                'phone' => isset($_POST['phone']) ? trim($_POST['phone']) : null,
                'first_name' => trim($_POST['first_name']),
                'last_name' => trim($_POST['last_name']),
                'bio' => trim($_POST['bio']),
                'first_name_err' => '',
                'last_name_err' => ''
            ];

            if (empty($data['first_name'])) {
                $this->sendJsonResponse(['error' => 'First name is required'], 400);
            }

            if (empty($data['last_name'])) {
                $this->sendJsonResponse(['error' => 'Last name is required'], 400);
            }

            if ($this->temporaryUserModel->updatePersonalDetails($data)) {
                $this->sendJsonResponse(['message' => 'Personal details saved successfully']);
            } else {
                $this->sendJsonResponse(['error' => 'Failed to save personal details'], 500);
            }
        } else {
            $this->sendJsonResponse(['error' => 'Invalid request method'], 405);
        }
    }

    public function collectAddress() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'email' => isset($_POST['email']) ? trim($_POST['email']) : null,
                'phone' => isset($_POST['phone']) ? trim($_POST['phone']) : null,
                'address' => trim($_POST['address']),
                'city' => trim($_POST['city']),
                'state' => trim($_POST['state']),
                'country' => trim($_POST['country']),
                'address_err' => '',
                'city_err' => '',
                'state_err' => '',
                'country_err' => ''
            ];

            if (empty($data['address'])) {
                $this->sendJsonResponse(['error' => 'Address is required'], 400);
            }
            if (empty($data['city'])) {
                $this->sendJsonResponse(['error' => 'City is required'], 400);
            }
            if (empty($data['state'])) {
                $this->sendJsonResponse(['error' => 'State is required'], 400);
            }
            if (empty($data['country'])) {
                $this->sendJsonResponse(['error' => 'Country is required'], 400);
            }

            if ($this->temporaryUserModel->updateAddress($data)) {
                $this->sendJsonResponse(['message' => 'Address saved successfully']);
            } else {
                $this->sendJsonResponse(['error' => 'Failed to save address'], 500);
            }
        } else {
            $this->sendJsonResponse(['error' => 'Invalid request method'], 405);
        }
    }

    public function uploadProfilePhoto() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
                $uploadDir = 'public/uploads/images/profile/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileName = uniqid() . '-' . basename($_FILES['photo']['name']);
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                    $data = [
                        'email' => isset($_POST['email']) ? trim($_POST['email']) : null,
                        'phone' => isset($_POST['phone']) ? trim($_POST['phone']) : null,
                        'profile_photo' => $targetPath
                    ];

                    if ($this->temporaryUserModel->updateProfilePhoto($data)) {
                        $this->sendJsonResponse(['message' => 'Profile photo uploaded successfully']);
                    } else {
                        $this->sendJsonResponse(['error' => 'Failed to save profile photo'], 500);
                    }
                } else {
                    $this->sendJsonResponse(['error' => 'Failed to upload file'], 500);
                }
            } else {
                $this->sendJsonResponse(['error' => 'No file uploaded or an error occurred'], 400);
            }
        } else {
            $this->sendJsonResponse(['error' => 'Invalid request method'], 405);
        }
    }

    public function selectRole() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'email' => isset($_POST['email']) ? trim($_POST['email']) : null,
                'phone' => isset($_POST['phone']) ? trim($_POST['phone']) : null,
                'role' => trim($_POST['role']),
                'role_err' => ''
            ];

            if (empty($data['role'])) {
                $this->sendJsonResponse(['error' => 'Role is required'], 400);
            }

            if ($tempUser = $this->temporaryUserModel->findTemporaryUser($data)) {
                $userData = [
                    'first_name' => $tempUser->first_name,
                    'last_name' => $tempUser->last_name,
                    'email' => $tempUser->email,
                    'phone' => $tempUser->phone,
                    'password' => $tempUser->password,
                    'bio' => $tempUser->bio,
                    'address' => $tempUser->address,
                    'city' => $tempUser->city,
                    'state' => $tempUser->state,
                    'country' => $tempUser->country,
                    'profile_photo' => $tempUser->profile_photo,
                    'role' => $data['role'],
                    'is_onboarded' => 1
                ];

                if ($this->userModel->register($userData)) {
                    $this->temporaryUserModel->deleteTemporaryUser($tempUser->id);
                    $this->sendJsonResponse(['message' => 'User registered successfully']);
                } else {
                    $this->sendJsonResponse(['error' => 'Failed to register user'], 500);
                }
            } else {
                $this->sendJsonResponse(['error' => 'Temporary user not found'], 404);
            }
        } else {
            $this->sendJsonResponse(['error' => 'Invalid request method'], 405);
        }
    }
    
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'email' => isset($_POST['email']) ? trim($_POST['email']) : null,
                'phone' => isset($_POST['phone']) ? trim($_POST['phone']) : null,
                'email_err' => '',
                'phone_err' => ''
            ];

            if (empty($data['email']) && empty($data['phone'])) {
                $this->sendJsonResponse(['error' => 'Either email or phone is required'], 400);
            }

            if (!empty($data['email'])) {
                if ($this->userModel->findUserByEmail($data['email'])) {
                    $this->sendJsonResponse(['error' => 'Email is already taken'], 400);
                }
                $this->sendOtp(['email' => $data['email']]);
            }

            if (!empty($data['phone'])) {
                if ($this->userModel->findUserByPhone($data['phone'])) {
                    $this->sendJsonResponse(['error' => 'Phone is already taken'], 400);
                }
                $this->sendOtp(['phone' => $data['phone']]);
            }
        } else {
            $this->sendJsonResponse(['error' => 'Invalid request method'], 405);
        }
    }

    public function googleLogin() {
        $client = new Google_Client();
        $client->setClientId(GOOGLE_CLIENT_ID);
        $client->setClientSecret(GOOGLE_CLIENT_SECRET);
        $client->setRedirectUri(GOOGLE_REDIRECT_URI);
        $client->addScope("email");
        $client->addScope("profile");

        $auth_url = $client->createAuthUrl();
        header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
    }

    public function googleOauth() {
        if (isset($_GET['code'])) {
            $client = new Google_Client();
            $client->setClientId(GOOGLE_CLIENT_ID);
            $client->setClientSecret(GOOGLE_CLIENT_SECRET);
            $client->setRedirectUri(GOOGLE_REDIRECT_URI);
            
            $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
            $client->setAccessToken($token['access_token']);

            $google_oauth = new Google_Service_Oauth2($client);
            $google_account_info = $google_oauth->userinfo->get();
            $email =  $google_account_info->email;
            $name =  $google_account_info->name;

            if ($user = $this->userModel->findUserByEmail($email)) {
                // User exists, log them in
                $this->createSendToken($user);
            } else {
                // User does not exist, register and log them in
                $data = [
                    'first_name' => $google_account_info->givenName,
                    'last_name' => $google_account_info->familyName,
                    'email' => $email,
                    'phone' => null,
                    'password' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                    'bio' => null,
                    'address' => null,
                    'city' => null,
                    'state' => null,
                    'country' => null,
                    'profile_photo' => $google_account_info->picture,
                    'role' => 'guest',
                    'is_onboarded' => 1
                ];

                if ($this->userModel->register($data)) {
                    $user = $this->userModel->findUserByEmail($email);
                    $this->createSendToken($user);
                } else {
                    $this->sendJsonResponse(['error' => 'Failed to register user'], 500);
                }
            }
        } else {
            $this->sendJsonResponse(['error' => 'No code received'], 400);
        }
    }

    private function createSendToken($user) {
        $payload = [
            'iss' => URLROOT,
            'aud' => URLROOT,
            'iat' => time(),
            'exp' => time() + 3600, // 1 hour
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'phone' => $user->phone
            ]
        ];

        $jwt = \Firebase\JWT\JWT::encode($payload, JWT_SECRET, 'HS256');

        $this->sendJsonResponse(['token' => $jwt]);
    }

    public function sendOtp($data) {
        // Generate a 6-digit OTP
        $otp = rand(100000, 999999);

        $otpData = [
            'email' => isset($data['email']) ? $data['email'] : null,
            'phone' => isset($data['phone']) ? $data['phone'] : null,
            'otp' => $otp,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+10 minutes'))
        ];

        if ($this->otpModel->createOtp($otpData)) {
            // In a real application, you would send the email/SMS here
            $this->sendJsonResponse(['message' => 'OTP sent successfully']);
        } else {
            $this->sendJsonResponse(['error' => 'Failed to send OTP'], 500);
        }
    }

    public function verifyOtp() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'email' => trim($_POST['email']),
                'phone' => trim($_POST['phone']),
                'otp' => trim($_POST['otp']),
                'otp_err' => ''
            ];

            if (empty($data['otp'])) {
                $this->sendJsonResponse(['error' => 'OTP is required'], 400);
            }

            if ($otp = $this->otpModel->findOtp($data)) {
                $this->otpModel->deleteOtp($otp->id);
                $this->sendJsonResponse(['message' => 'OTP verified successfully']);
            } else {
                $this->sendJsonResponse(['error' => 'Invalid OTP'], 400);
            }
        } else {
            $this->sendJsonResponse(['error' => 'Invalid request method'], 405);
        }
    }
}
