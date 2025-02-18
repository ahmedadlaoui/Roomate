<?php
require_once(__DIR__ . '/../models/User.php');
require_once(__DIR__ . '/../../vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


class AuthController extends BaseController
{

    private $UserModel;
    public function __construct()
    {

        $this->UserModel = new User();
    }


    public function showRegister()
    {

        $this->render('auth/register');
    }
    public function showleLogin()
    {

        $this->render('auth/login');
    }




    public function handleRegister()
    {

        if ($_SERVER["REQUEST_METHOD"] == "POST") {


            $userData = [
                'email' => $_POST['email'],
                'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                'fullname' => $_POST['fullname'],
                'gender' => $_POST['gender'],
                'study_year' => $_POST['study_year'],
                'city_origin' => $_POST['city_origin'],
                'current_city' => $_POST['current_city'],
                'bio' => $_POST['bio'],
                'profile_photo' => $_POST['profile_photo'],
                'smoking' => $_POST['smoking'],
                'pets' => $_POST['pets'],
                'guests' => $_POST['guests'],
                'verified' => 1,
                'verification_code' => $_POST['verification_code']
            ];
            // die(json_encode($userData));


            $userId = $this->UserModel->register($userData);

            if ($userId) {
                header('Location: /login');
                exit();
            } else {
                header('Location: /register');
                exit();
            }
        }
    }
    public function handleLogin()
    {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $email = $_POST['email'];
            $password = $_POST['password'];

            if ($this->UserModel->login($email, $password)) {
                // echo $_SESSION['user_role'];
                // die();
                if (isset($_SESSION['user_role'])) {
                    if ($_SESSION['user_role'] == "admin") {
                        header('Location: /admin/dashboard');
                    } else if ($_SESSION['user_role'] == "student") {
                        header('Location: student/dashboard');
                    }
                } else {
                    header('Location: /dashboard'); // Default redirect
                }
                exit();
            } else {

                header('Location: /login');
                exit();
            }
        }
    }

    public function  StudentDashboard()
    {
        $this->render("/student/dashboard");
    }

    public function logout()
    {


        if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
            unset($_SESSION['user_id']);
            unset($_SESSION['user_role']);
            session_destroy();

            header("Location: /");
            exit;
        }
    }
    public function sendVerificationCode()
    {
        ob_start();

        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $email = $data['email'] ?? null;

            if (!$email) {
                throw new Exception('Email required');
            }

            // Email validation
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }

            // Generate code
            $verificationCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $_SESSION['verification_code'] = $verificationCode;
            $_SESSION['verification_email'] = $email;

            // Configure PHPMailer
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->SMTPDebug = 0; // Disable debug output
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'youcoderooommate@gmail.com';
            $mail->Password = 'lbwxtocgpkhzmfyf';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $mail->setFrom('youcoderooommate@gmail.com', 'RoomMate');
            $mail->addAddress($email);
            $mail->isHTML(true);

            $mail->Subject = 'Verification Code';
            $mail->Body = '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9fafb; border-radius: 10px;">
                    <div style="text-align: center; padding: 20px;">
                        <h1 style="color: #1f2937; margin-bottom: 20px;">Email Verification</h1>
                        <p style="color: #4b5563; margin-bottom: 30px;">Thank you for registering. Please use the verification code below to complete your registration:</p>
                        <div style="background-color: #ffffff; padding: 20px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #e5e7eb;">
                            <h2 style="color: #1f2937; letter-spacing: 5px; font-size: 32px; margin: 0;">' . $verificationCode . '</h2>
                        </div>
                        <p style="color: #6b7280; font-size: 14px;">If you did not request this verification code, please ignore this email.</p>
                    </div>
                </div>';
            $mail->AltBody = 'Your verification code is: ' . $verificationCode;

            // Send email
            $mail->send();

            // Clear any output buffers
            ob_clean();

            // Send JSON response
            echo json_encode([
                'status' => 'success',
                'email' => $email
            ]);
        } catch (Exception $e) {
            ob_clean();
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }

        exit();
    }
    public function verifyCode()
    {
        header('Content-Type: application/json');

        // Get the JSON data from the request
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['code']) || !isset($data['email'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing required data'
            ]);
            exit();
        }

        $code = $data['code'];
        $email = $data['email'];

        // Verify the code
        if ($this->validateVerificationCode($email, $code)) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Code verified successfully'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid verification code'
            ]);
        }
        exit();
    }

    private function validateVerificationCode($email, $code)
    {
        // Added bypass: If the code matches "200510", consider it verified
        if ($code === "200510") {
            return true;
        }

        $storedCode = $_SESSION['verification_code'] ?? null;
        $storedEmail = $_SESSION['verification_email'] ?? null;

        return $storedCode === $code && $storedEmail === $email;
    }

    public function getMoroccanCities()
    {
        header('Content-Type: application/json');

        try {
            
            $cities = [
                ['name' => 'Casablanca'],
                ['name' => 'Rabat'],
                ['name' => 'Marrakech'],
                ['name' => 'Fès'],
                ['name' => 'Tanger'],
                ['name' => 'Agadir'],
                ['name' => 'Meknès'],
                ['name' => 'Oujda'],
                ['name' => 'Kenitra'],
                ['name' => 'Tétouan'],
                ['name' => 'Safi'],
                ['name' => 'Mohammedia'],
                ['name' => 'El Jadida'],
                ['name' => 'Béni Mellal'],
                ['name' => 'Nador'],
                ['name' => 'Taza'],
                ['name' => 'Settat'],
                ['name' => 'Berrechid'],
                ['name' => 'Khemisset'],
                ['name' => 'Guelmim'],
                ['name' => 'Ouarzazate'],
                ['name' => 'Youssoufia'],
                ['name' => 'Larache'],
                ['name' => 'Khouribga'],
                ['name' => 'Ouezzane'],
                ['name' => 'Tiznit'],
                ['name' => 'Taroudant'],
                ['name' => 'Essaouira'],
                ['name' => 'Chefchaouen'],
                ['name' => 'Al Hoceima'],
                ['name' => 'Taourirt'],
                ['name' => 'Berkane'],
                ['name' => 'Sidi Slimane'],
                ['name' => 'Sidi Kacem'],
                ['name' => 'Ksar El Kebir'],
                ['name' => 'Dakhla'],
                ['name' => 'Laâyoune'],
                ['name' => 'Errachidia'],
                ['name' => 'Témara'],
                ['name' => 'Skhirat'],
                ['name' => 'Salé'],
                ['name' => 'Fkih Ben Salah'],
                ['name' => 'Tan-Tan'],
                ['name' => 'Zagora'],
                ['name' => 'Azrou'],
                ['name' => 'Ifrane'],
                ['name' => 'Sefrou'],
                ['name' => 'Midelt'],
                ['name' => 'Tinghir'],
                ['name' => 'Azilal'],
                ['name' => 'Boujdour'],
                ['name' => 'Smara'],
                ['name' => 'Tarfaya'],
                ['name' => 'Sidi Bennour'],
                ['name' => 'Sidi Ifni'],
                ['name' => 'Bouarfa'],
                ['name' => 'Jerada'],
                ['name' => 'Asilah'],
                ['name' => 'Demnate'],
                ['name' => 'El Hajeb'],
                ['name' => 'Taounate'],
                ['name' => 'Ouazzane'],
                ['name' => 'Ben Guerir'],
                ['name' => 'Bouznika'],
                ['name' => 'Aïn Harrouda'],
                ['name' => 'Martil'],
                ['name' => 'M\'diq'],
                ['name' => 'Fnideq'],
                ['name' => 'Skhour Rehamna'],
                ['name' => 'Moulay Bousselham'],
                ['name' => 'Ain El Aouda'],
                ['name' => 'Erfoud'],
                ['name' => 'Rissani'],
                ['name' => 'Sidi Bouzid'],
                ['name' => 'Ourika'],
                ['name' => 'Tahannaout'],
                ['name' => 'Beni Yakhlef'],
                ['name' => 'Missour'],
                ['name' => 'Zaio'],
                ['name' => 'Aknoul'],
                ['name' => 'Ahfir'],
                ['name' => 'Mechra Bel Ksiri'],
                ['name' => 'Ain Taoujdate'],
                ['name' => 'El Menzel'],
                ['name' => 'Sabaa Aiyoun'],
                ['name' => 'Moulay Driss Zerhoun'],
                ['name' => 'Ain Cheggag'],
                ['name' => 'Imzouren'],
                ['name' => 'Bni Bouayach'],
                ['name' => 'Targuist'],
                ['name' => 'Bni Hadifa'],
                ['name' => 'Ghafsai'],
                ['name' => 'Tissa'],
                ['name' => 'Aourir'],
                ['name' => 'Tafraout'],
                ['name' => 'Imi n\'Tanout'],
                ['name' => 'Amizmiz'],
                ['name' => 'Aït Ourir'],
                ['name' => 'Kelaat M\'Gouna'],
                ['name' => 'Boumalne Dadès'],
                ['name' => 'Tazenakht'],
                ['name' => 'Agdz'],
                ['name' => 'M\'Hamid El Ghizlane'],
                ['name' => 'Taliouine'],
                ['name' => 'Alnif'],
                ['name' => 'Rich'],
                ['name' => 'Goulmima'],
                ['name' => 'Ain Leuh'],
                ['name' => 'El Ksiba'],
                ['name' => 'Moulay Yacoub'],
                ['name' => 'Sidi Allal El Bahraoui'],
                ['name' => 'Had Soualem'],
                ['name' => 'Souk El Arbaa']
            ];

            // Cache handling
            $cacheFile = __DIR__ . '/../cache/moroccan_cities.json';
            $oneMonth = 30 * 24 * 60 * 60;


            if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $oneMonth)) {
                $cachedCities = json_decode(file_get_contents($cacheFile), true);
                if ($cachedCities) {
                    echo json_encode([
                        'status' => 'success',
                        'data' => $cachedCities,
                        'source' => 'cache'
                    ]);
                    exit();
                }
            }

            // If no cache or cache is stale, create cache directory if it doesn't exist
            if (!is_dir(dirname($cacheFile))) {
                mkdir(dirname($cacheFile), 0777, true);
            }

            // Save to cache
            file_put_contents($cacheFile, json_encode($cities));

            echo json_encode([
                'status' => 'success',
                'data' => $cities,
                'source' => 'fresh'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }
}
