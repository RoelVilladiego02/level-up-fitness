<?php
/**
 * Email Notification Helper Functions
 * Level Up Fitness - Gym Management System
 * 
 * All email notification functions using SMTP
 * These functions abstract the SMTP service and provide
 * easy-to-use interfaces for sending notifications
 */

require_once dirname(__FILE__) . '/../config/SMTPMailService.php';

/**
 * Render email template with variables
 * 
 * @param string $templateName Name of template file (without .html)
 * @param array $variables Template variables to replace
 * @return string Rendered HTML
 */
function renderEmailTemplate($templateName, $variables = []) {
    $templateFile = EMAIL_TEMPLATE_DIR . $templateName . '.html';
    
    if (!file_exists($templateFile)) {
        error_log("Email template not found: $templateFile");
        return '';
    }
    
    $html = file_get_contents($templateFile);
    
    // Replace all {{VARIABLE}} with actual values
    foreach ($variables as $key => $value) {
        $placeholder = '{{' . strtoupper($key) . '}}';
        $html = str_replace($placeholder, htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $html);
    }
    
    // Remove any unreplaced variables
    $html = preg_replace('/\{\{[A-Z_]+\}\}/', '', $html);
    
    return $html;
}

/**
 * Send Payment Notification Email
 * 
 * @param string $memberEmail Member's email address
 * @param string $memberName Member's full name
 * @param array $paymentData Payment details
 * @return array Result from MailtrapService::send()
 */
function sendPaymentConfirmationEmail($memberEmail, $memberName, $paymentData) {
    
    $variables = [
        'member_name' => $memberName,
        'payment_id' => $paymentData['payment_id'] ?? 'N/A',
        'amount' => number_format($paymentData['amount'] ?? 0, 2),
        'payment_method' => $paymentData['payment_method'] ?? 'Not specified',
        'status' => $paymentData['status'] ?? 'Pending',
        'payment_date' => $paymentData['payment_date'] ?? date('M d, Y H:i'),
        'membership_type' => $paymentData['membership_type'] ?? '',
        'membership_start' => $paymentData['membership_start'] ?? '',
        'membership_end' => $paymentData['membership_end'] ?? '',
        'dashboard_url' => APP_URL . 'dashboard/',
        'support_url' => APP_URL . 'support/',
        'website_url' => APP_URL,
    ];
    
    $htmlBody = renderEmailTemplate('payment-confirmation', $variables);
    
    return SMTPMailService::send(
        $memberEmail,
        'Payment Confirmation - Level Up Fitness',
        $htmlBody,
        "Payment received: ₱" . $variables['amount']
    );
}

/**
 * Send Reservation Confirmation Email
 * 
 * @param string $memberEmail Member's email
 * @param string $memberName Member's name
 * @param array $reservationData Reservation details
 * @return array Result
 */
function sendReservationConfirmationEmail($memberEmail, $memberName, $reservationData) {
    
    $variables = [
        'member_name' => $memberName,
        'reservation_id' => $reservationData['reservation_id'] ?? 'N/A',
        'equipment_name' => $reservationData['equipment_name'] ?? 'Equipment',
        'reservation_date' => $reservationData['reservation_date'] ?? date('M d, Y'),
        'start_time' => $reservationData['start_time'] ?? '00:00',
        'end_time' => $reservationData['end_time'] ?? '00:00',
        'duration' => $reservationData['duration'] ?? '0',
        'trainer_name' => $reservationData['trainer_name'] ?? '',
        'trainer_assigned' => !empty($reservationData['trainer_name']),
        'gym_address' => $reservationData['gym_address'] ?? 'Level Up Fitness',
        'cancellation_deadline' => $reservationData['cancellation_deadline'] ?? date('M d, Y'),
        'dashboard_url' => APP_URL . 'dashboard/',
        'support_url' => APP_URL . 'support/',
        'website_url' => APP_URL,
    ];
    
    $htmlBody = renderEmailTemplate('reservation-confirmation', $variables);
    
    return SMTPMailService::send(
        $memberEmail,
        'Reservation Confirmed - ' . $reservationData['equipment_name'],
        $htmlBody,
        "Your reservation for " . $reservationData['equipment_name'] . " is confirmed"
    );
}

/**
 * Send Member Welcome Email
 * 
 * @param string $memberEmail Member's email
 * @param string $memberName Member's name
 * @param array $memberData Member account information
 * @return array Result
 */
function sendMemberWelcomeEmail($memberEmail, $memberName, $memberData) {
    
    $variables = [
        'member_name' => $memberName,
        'username' => $memberData['username'] ?? '',
        'email' => $memberEmail,
        'member_id' => $memberData['member_id'] ?? 'N/A',
        'membership_type' => $memberData['membership_type'] ?? '',
        'membership_expiry' => $memberData['membership_expiry'] ?? '',
        'membership_info' => !empty($memberData['membership_type']),
        'trainer_assigned' => !empty($memberData['trainer_name']),
        'trainer_name' => $memberData['trainer_name'] ?? '',
        'trainer_email' => $memberData['trainer_email'] ?? '',
        'login_url' => APP_URL . 'auth/login.php',
        'dashboard_url' => APP_URL . 'dashboard/',
        'support_url' => APP_URL . 'support/',
        'website_url' => APP_URL,
    ];
    
    $htmlBody = renderEmailTemplate('member-welcome', $variables);
    
    return SMTPMailService::send(
        $memberEmail,
        'Welcome to Level Up Fitness!',
        $htmlBody,
        "Welcome " . $memberName . "! Your account is ready."
    );
}

/**
 * Send Password Reset Email
 * 
 * @param string $memberEmail Member's email
 * @param string $memberName Member's name
 * @param string $resetToken Password reset token
 * @param int $expirationHours How many hours until token expires
 * @return array Result
 */
function sendPasswordResetEmail($memberEmail, $memberName, $resetToken, $expirationHours = 24) {
    
    $resetLink = APP_URL . 'auth/reset-password.php?token=' . urlencode($resetToken);
    
    $variables = [
        'member_name' => $memberName,
        'email' => $memberEmail,
        'reset_link' => $resetLink,
        'expiration_time' => $expirationHours,
        'request_time' => date('M d, Y H:i A'),
        'support_url' => APP_URL . 'support/',
        'dashboard_url' => APP_URL . 'dashboard/',
        'website_url' => APP_URL,
    ];
    
    $htmlBody = renderEmailTemplate('password-reset', $variables);
    
    return SMTPMailService::send(
        $memberEmail,
        'Password Reset Request - Level Up Fitness',
        $htmlBody,
        "Click the link to reset your password"
    );
}

/**
 * Send Membership Expiring Soon Email
 * 
 * @param string $memberEmail Member's email
 * @param string $memberName Member's name
 * @param array $membershipData Membership information
 * @param array $renewalPlans Available renewal options
 * @return array Result
 */
function sendMembershipExpiringEmail($memberEmail, $memberName, $membershipData, $renewalPlans = []) {
    
    $expirationDate = strtotime($membershipData['expiration_date']);
    $daysRemaining = max(0, floor(($expirationDate - time()) / 86400));
    
    $variables = [
        'member_name' => $memberName,
        'membership_type' => $membershipData['membership_type'] ?? 'N/A',
        'expiration_date' => $membershipData['expiration_date'] ?? date('M d, Y'),
        'days_remaining' => $daysRemaining,
        'renewal_url' => APP_URL . 'modules/memberships/renew.php',
        'dashboard_url' => APP_URL . 'dashboard/',
        'support_url' => APP_URL . 'support/',
        'website_url' => APP_URL,
    ];
    
    $htmlBody = renderEmailTemplate('membership-expiring-soon', $variables);
    
    return SMTPMailService::send(
        $memberEmail,
        'Your Membership is Expiring Soon',
        $htmlBody,
        "Renew your membership now - " . $daysRemaining . " days remaining"
    );
}

/**
 * Send Trainer Assignment Email
 * 
 * @param string $memberEmail Member's email
 * @param string $memberName Member's name
 * @param array $trainerData Trainer information
 * @return array Result
 */
function sendTrainerAssignmentEmail($memberEmail, $memberName, $trainerData) {
    
    $variables = [
        'member_name' => $memberName,
        'trainer_name' => $trainerData['trainer_name'] ?? 'Trainer',
        'trainer_email' => $trainerData['trainer_email'] ?? 'trainer@levelupfitness.local',
        'trainer_phone' => $trainerData['trainer_phone'] ?? 'N/A',
        'trainer_specialization' => $trainerData['trainer_specialization'] ?? 'General Fitness',
        'trainer_bio' => $trainerData['trainer_bio'] ?? '',
        'first_session_details' => !empty($trainerData['session_date']),
        'session_date' => $trainerData['session_date'] ?? '',
        'session_time' => $trainerData['session_time'] ?? '',
        'session_location' => $trainerData['session_location'] ?? '',
        'dashboard_url' => APP_URL . 'dashboard/',
        'support_url' => APP_URL . 'support/',
        'website_url' => APP_URL,
    ];
    
    $htmlBody = renderEmailTemplate('trainer-assignment', $variables);
    
    return SMTPMailService::send(
        $memberEmail,
        'Your Trainer Assignment - ' . $trainerData['trainer_name'],
        $htmlBody,
        "You've been assigned trainer " . $trainerData['trainer_name']
    );
}

/**
 * Send Workout Plan Created Email
 * 
 * @param string $memberEmail Member's email
 * @param string $memberName Member's name
 * @param array $planData Workout plan information
 * @return array Result
 */
function sendWorkoutPlanEmail($memberEmail, $memberName, $planData) {
    
    $variables = [
        'member_name' => $memberName,
        'plan_name' => $planData['plan_name'] ?? 'Workout Plan',
        'trainer_name' => $planData['trainer_name'] ?? 'Your Trainer',
        'trainer_email' => $planData['trainer_email'] ?? '',
        'plan_duration' => $planData['duration_weeks'] ?? '4',
        'focus_area' => $planData['focus_area'] ?? 'General Fitness',
        'difficulty_level' => $planData['difficulty_level'] ?? 'Intermediate',
        'sessions_per_week' => $planData['sessions_per_week'] ?? '3',
        'plan_description' => $planData['description'] ?? '',
        'plan_url' => APP_URL . 'modules/workouts/view.php?id=' . ($planData['plan_id'] ?? ''),
        'dashboard_url' => APP_URL . 'dashboard/',
        'support_url' => APP_URL . 'support/',
        'website_url' => APP_URL,
    ];
    
    $htmlBody = renderEmailTemplate('workout-plan-created', $variables);
    
    return SMTPMailService::send(
        $memberEmail,
        'Your Workout Plan is Ready - ' . $planData['plan_name'],
        $htmlBody,
        "Your trainer created a new workout plan for you"
    );
}

/**
 * Send Class Reminder Email
 * 
 * @param string $memberEmail Member's email
 * @param string $memberName Member's name
 * @param array $classData Class information
 * @return array Result
 */
function sendClassReminderEmail($memberEmail, $memberName, $classData) {
    
    $variables = [
        'member_name' => $memberName,
        'class_name' => $classData['class_name'] ?? 'Class',
        'trainer_name' => $classData['trainer_name'] ?? 'Trainer',
        'class_date' => $classData['class_date'] ?? date('M d, Y'),
        'start_time' => $classData['start_time'] ?? '09:00',
        'end_time' => $classData['end_time'] ?? '10:00',
        'class_location' => $classData['class_location'] ?? 'Level Up Fitness',
        'current_participants' => $classData['current_participants'] ?? '0',
        'max_capacity' => $classData['max_capacity'] ?? '20',
        'class_description' => $classData['description'] ?? '',
        'cancel_url' => APP_URL . 'modules/classes/cancel.php?id=' . ($classData['class_id'] ?? ''),
        'dashboard_url' => APP_URL . 'dashboard/',
        'support_url' => APP_URL . 'support/',
        'website_url' => APP_URL,
    ];
    
    $htmlBody = renderEmailTemplate('class-reminder', $variables);
    
    return SMTPMailService::send(
        $memberEmail,
        'Reminder: ' . $classData['class_name'] . ' on ' . $classData['class_date'],
        $htmlBody,
        "Don't forget your class tomorrow!"
    );
}

/**
 * Send Reservation Cancellation Email
 * 
 * @param string $memberEmail Member's email
 * @param string $memberName Member's name
 * @param array $cancellationData Cancellation details
 * @return array Result
 */
function sendReservationCancellationEmail($memberEmail, $memberName, $cancellationData) {
    
    $variables = [
        'member_name' => $memberName,
        'reservation_id' => $cancellationData['reservation_id'] ?? 'N/A',
        'equipment_name' => $cancellationData['equipment_name'] ?? 'Equipment',
        'reservation_date' => $cancellationData['reservation_date'] ?? date('M d, Y'),
        'start_time' => $cancellationData['start_time'] ?? '00:00',
        'end_time' => $cancellationData['end_time'] ?? '00:00',
        'cancellation_reason' => $cancellationData['reason'] ?? 'No reason provided',
        'cancellation_date' => $cancellationData['cancellation_date'] ?? date('M d, Y H:i'),
        'refund_applicable' => !empty($cancellationData['refund_amount']) && $cancellationData['refund_amount'] > 0,
        'refund_amount' => number_format($cancellationData['refund_amount'] ?? 0, 2),
        'refund_days' => $cancellationData['refund_days'] ?? '3',
        'cancellation_fee' => !empty($cancellationData['cancellation_fee']) ? number_format($cancellationData['cancellation_fee'], 2) : '',
        'reservation_url' => APP_URL . 'modules/reservations/',
        'dashboard_url' => APP_URL . 'dashboard/',
        'support_url' => APP_URL . 'support/',
        'website_url' => APP_URL,
    ];
    
    $htmlBody = renderEmailTemplate('reservation-cancelled', $variables);
    
    return SMTPMailService::send(
        $memberEmail,
        'Reservation Cancelled - ' . $cancellationData['equipment_name'],
        $htmlBody,
        "Your reservation has been cancelled"
    );
}

/**
 * Generic email sending function
 * 
 * @param string|array $recipient Email address(es)
 * @param string $subject Email subject
 * @param string $htmlBody HTML body
 * @param string $textBody Plain text body (optional)
 * @param array $options Additional options (cc, bcc, headers, etc.)
 * @return array Result
 */
function sendCustomEmail($recipient, $subject, $htmlBody, $textBody = '', $options = []) {
    return SMTPMailService::send($recipient, $subject, $htmlBody, $textBody, $options);
}

/**
 * Send bulk emails
 * 
 * @param array $emails Array of email data
 * @return array Results
 */
function sendBulkEmails($emails) {
    return SMTPMailService::sendBulk($emails);
}

/**
 * Test SMTP configuration
 * 
 * @param string $testEmail Email to send test to
 * @return array Result
 */
function testMailtrapConfiguration($testEmail = '') {
    return SMTPMailService::sendTest($testEmail);
}

?>
