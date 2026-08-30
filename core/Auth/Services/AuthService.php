<?php
// Path: core/Auth/Services/AuthService.php
// (هذا تحديث للملف السابق لإضافة ميزات الإيميل والاسترداد)

namespace Core\Auth\Services;

use Core\Auth\Repositories\UserRepository;
use Core\Events\EventDispatcher;
use Core\Events\Auth\UserLoggedInEvent;
use Core\Notifications\EmailService;
use Exception;

class AuthService
{
    private UserRepository $userRepository;
    private ?EventDispatcher $dispatcher;
    private EmailService $emailService;
    
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    public function __construct(UserRepository $userRepository, ?EventDispatcher $dispatcher = null, ?EmailService $emailService = null)
    {
        $this->userRepository = $userRepository;
        $this->dispatcher = $dispatcher;
        $this->emailService = $emailService ?? new EmailService(); // حقن خدمة الإيميل
    }

    /**
     * معالجة محاولة تسجيل الدخول
     */
    public function attemptLogin(array $credentials): array
    {
        $locale = $_SESSION['locale'] ?? 'ar';
        $user = $this->userRepository->findByEmail($credentials['email']);

        // 1. حساب غير موجود (رسالة عامة لأسباب أمنية)
        if (!$user) {
            throw new Exception(__('auth.failed', [], $locale));
        }

        // 2. التحقق من حماية الـ Brute Force (هل الحساب مقفول؟)
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $minutesLeft = ceil((strtotime($user['locked_until']) - time()) / 60);
            throw new Exception("تم قفل حسابك مؤقتاً بسبب محاولات خاطئة كثيرة. حاول مجدداً بعد {$minutesLeft} دقيقة.");
        }

        // 3. التحقق من كلمة المرور
        if (!password_verify($credentials['password'], $user['password'])) {
            $this->handleFailedAttempt($user);
            throw new Exception(__('auth.failed', [], $locale));
        }

        // 4. الباسورد صحيح -> تصفير عداد المحاولات الخاطئة
        if ($user['login_attempts'] > 0) {
            $this->userRepository->resetLoginAttempts($user['id']);
        }

        // 5. التحقق من الـ Two Factor Authentication
        if ($user['two_factor_enabled']) {
            $_SESSION['2fa_pending_user_id'] = $user['id'];
            // TODO: Generate and send OTP via NotificationService here
            return ['status' => 'requires_2fa', 'user' => $user];
        }

        // 6. تسجيل الدخول النهائي وإنشاء الجلسة
        $this->finalizeLogin($user, $credentials['ip'], $credentials['user_agent']);
        
        return ['status' => 'success', 'user' => $user];
    }

    /**
     * معالجة المحاولات الخاطئة (Brute Force Protection)
     */
    private function handleFailedAttempt(array $user): void
    {
        $attempts = $user['login_attempts'] + 1;
        
        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            // قفل الحساب
            $lockUntil = date('Y-m-d H:i:s', strtotime('+' . self::LOCKOUT_MINUTES . ' minutes'));
            $this->userRepository->lockAccount($user['id'], $lockUntil);
        } else {
            // زيادة العداد
            $this->userRepository->incrementLoginAttempts($user['id']);
        }
    }

    /**
     * إتمام تسجيل الدخول وإطلاق الـ Events
     */
    private function finalizeLogin(array $user, string $ip, string $userAgent): void
    {
        // إعداد الجلسة (Session)
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['locale'] = $user['language'] ?? 'ar';
        
        // تسجيل الجلسة في قاعدة البيانات (Session Management)
        $sessionId = session_id();
        $this->userRepository->logSession($user['id'], $sessionId, $ip, $userAgent);

        // إطلاق حدث (Domain Event) ليقوم نظام الـ Audit بتسجيله في الخلفية
        if ($this->dispatcher) {
            $event = new UserLoggedInEvent($user['id'], $ip, $userAgent);
            $this->dispatcher->dispatch($event);
        }
    }

    public function logout(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->userRepository->revokeSession(session_id());
        }
        session_unset();
        session_destroy();
    }
    public function generateAndSendOTP(array $user): void
    {
        $otp = rand(100000, 999999);
        // TODO: Save $otp hashed in database with a 10-minute expiry
        
        $subject = "Your 2FA Verification Code";
        $body = "<h2>Hello {$user['name']}</h2><p>Your verification code is: <strong>{$otp}</strong></p><p>This code expires in 10 minutes.</p>";
        
        $this->emailService->send($user['email'], $subject, $body);
    }

    /**
     * التحقق من كود الاسترداد (Recovery Code)
     */
    public function verifyRecoveryCode(int $userId, string $code): bool
    {
        // TODO: Fetch user's recovery codes from DB, hash the input, and compare.
        // If valid -> mark code as used in DB -> return true
        
        // محاكاة
        if ($code === 'RC-12345-67890') {
            return true;
        }
        return false;
    }

    /**
     * معالجة طلب استعادة كلمة المرور (Forgot Password)
     */
    public function processForgotPassword(string $email): void
    {
        $user = $this->userRepository->findByEmail($email);
        if ($user) {
            $token = bin2hex(random_bytes(32)); // توليد توكن آمن
            // TODO: Save token to password_resets table with expiry (e.g., 1 hour)
            
            $resetLink = "https://nourtrust.com/ERP/reset-password?token=" . $token;
            $subject = "Password Reset Request";
            $body = "<p>Click the link below to reset your password:</p><a href='{$resetLink}'>Reset Password</a>";
            
            $this->emailService->send($user['email'], $subject, $body);
        }
        // لا نرمي Exception إذا لم يوجد الحساب، لمنع كشف الحسابات المسجلة.
    }
}
