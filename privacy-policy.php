<?php
/**
 * Privacy Policy Page
 * Level Up Fitness - Gym Management System
 * Compliant with Republic Act No. 10173 (Data Privacy Act of 2012)
 */

require_once dirname(__FILE__) . '/config/config.php';
require_once dirname(__FILE__) . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body p-5">
                    <h1 class="mb-3"><i class="fas fa-lock"></i> Privacy Policy</h1>
                    <p class="text-muted mb-4">Last Updated: May 2026</p>
                    
                    <div class="privacy-content">
                        <!-- Introduction -->
                        <section class="mb-5">
                            <h3 class="mb-3">1. Introduction</h3>
                            <p>
                                Level Up Fitness ("the Company", "we", "us", or "our") is committed to protecting your personal data and respecting your privacy. 
                                This Privacy Policy explains how we collect, use, disclose, and safeguard your information in accordance with 
                                <strong>Republic Act No. 10173 (Data Privacy Act of 2012)</strong> and its implementing rules and regulations.
                            </p>
                            <p>
                                Our Privacy Policy applies to all users of our gym management system, including members, trainers, and administrators.
                                Please read this policy carefully. If you do not agree with our practices, please do not use our services.
                            </p>
                        </section>

                        <!-- Personal Data Collected -->
                        <section class="mb-5">
                            <h3 class="mb-3">2. Personal Data We Collect</h3>
                            <p>We collect personal data that you provide directly to us and data collected through your use of our system:</p>
                            
                            <h5 class="mt-3 mb-2">A. Data Provided by Members:</h5>
                            <ul>
                                <li>Full Name</li>
                                <li>Email Address</li>
                                <li>Contact Number (Landline/Mobile)</li>
                                <li>Physical Address</li>
                                <li>Date of Birth</li>
                                <li>Membership Type and Duration</li>
                                <li>Payment Information (transaction history, but NOT credit card details stored)</li>
                                <li>Health/Fitness Information (workouts, attendance records, session history)</li>
                            </ul>

                            <h5 class="mt-3 mb-2">B. Data Provided by Trainers:</h5>
                            <ul>
                                <li>Full Name</li>
                                <li>Email Address</li>
                                <li>Contact Number</li>
                                <li>Professional Qualifications and Specialization</li>
                                <li>Years of Experience</li>
                                <li>Physical Address</li>
                            </ul>

                            <h5 class="mt-3 mb-2">C. System-Generated Data:</h5>
                            <ul>
                                <li>User ID (automatically generated)</li>
                                <li>Login Activity and Timestamps</li>
                                <li>Last Login Date and Time</li>
                                <li>Action Logs (activities performed in the system)</li>
                                <li>IP Address (from login)</li>
                                <li>Device Information</li>
                            </ul>
                        </section>

                        <!-- Basis and Purpose -->
                        <section class="mb-5">
                            <h3 class="mb-3">3. Basis and Purpose for Processing</h3>
                            <p>Under the Data Privacy Act, we process your personal data for the following legitimate purposes:</p>
                            
                            <h5 class="mt-3 mb-2">a. Fulfillment of Service</h5>
                            <ul>
                                <li>To create and manage your account</li>
                                <li>To provide gym membership services</li>
                                <li>To schedule training sessions and classes</li>
                                <li>To track attendance and progress</li>
                                <li>To process payments for membership and services</li>
                            </ul>

                            <h5 class="mt-3 mb-2">b. Communication</h5>
                            <ul>
                                <li>To send account verification emails</li>
                                <li>To notify you about membership status and expirations</li>
                                <li>To provide updates about services and promotions</li>
                                <li>To respond to your inquiries and support requests</li>
                            </ul>

                            <h5 class="mt-3 mb-2">c. Legal and Compliance</h5>
                            <ul>
                                <li>To comply with legal obligations under Philippine laws</li>
                                <li>To fulfill our mandate as a gym management entity</li>
                                <li>To maintain audit trails and activity logs for security</li>
                                <li>To prevent fraud and unauthorized access</li>
                            </ul>

                            <h5 class="mt-3 mb-2">d. System Security</h5>
                            <ul>
                                <li>To protect the integrity of our systems</li>
                                <li>To detect and prevent unauthorized access attempts</li>
                                <li>To investigate security incidents</li>
                            </ul>
                        </section>

                        <!-- Data Storage and Retention -->
                        <section class="mb-5">
                            <h3 class="mb-3">4. Data Storage and Retention</h3>
                            <p><strong>Storage Location:</strong> Your personal data is stored on our secure servers located in the Philippines.</p>
                            <p><strong>Retention Period:</strong></p>
                            <ul>
                                <li><strong>Active Members/Trainers:</strong> Data retained throughout membership validity and 2 years after account termination</li>
                                <li><strong>Payment Records:</strong> Retained for 5 years (per Philippine tax laws)</li>
                                <li><strong>Activity Logs:</strong> Retained for 1 year for security and audit purposes</li>
                                <li><strong>Inactive Accounts:</strong> Data deleted upon written request or after 3 years of inactivity (unless legally required)</li>
                            </ul>
                            <p>
                                After the retention period expires, your data will be securely destroyed through deletion or anonymization. 
                                We may retain certain data if required by law.
                            </p>
                        </section>

                        <!-- Data Security -->
                        <section class="mb-5">
                            <h3 class="mb-3">5. Data Security and Protection Measures</h3>
                            <p>We implement the following security measures to protect your personal data:</p>
                            <ul>
                                <li><strong>Encryption:</strong> Data transmitted and stored using industry-standard encryption</li>
                                <li><strong>Access Control:</strong> Only authorized personnel have access to your personal data</li>
                                <li><strong>Password Protection:</strong> Your password is hashed and never stored in plain text</li>
                                <li><strong>Secure Authentication:</strong> Session management with automatic timeout (30 minutes of inactivity)</li>
                                <li><strong>CSRF Protection:</strong> Cross-Site Request Forgery tokens to prevent unauthorized actions</li>
                                <li><strong>Regular Backups:</strong> Automated backups to prevent data loss</li>
                                <li><strong>Physical Security:</strong> Servers are protected in secure facilities</li>
                                <li><strong>Audit Logging:</strong> All system activities are logged and monitored</li>
                            </ul>
                            <p class="text-warning mt-3">
                                <i class="fas fa-exclamation-triangle"></i> 
                                <strong>Note:</strong> While we employ robust security measures, no method of transmission over the internet is 100% secure. 
                                We cannot guarantee absolute security of your data against all potential threats.
                            </p>
                        </section>

                        <!-- Data Subject Rights -->
                        <section class="mb-5">
                            <h3 class="mb-3">6. Your Data Privacy Rights</h3>
                            <p>
                                Under the Data Privacy Act of 2012, you have the following rights regarding your personal data:
                            </p>
                            
                            <h5 class="mt-3 mb-2">a. Right to Be Informed</h5>
                            <p>You have the right to know how your personal data is being collected, processed, and used.</p>

                            <h5 class="mt-3 mb-2">b. Right to Access</h5>
                            <p>You can request access to your personal data we hold and obtain a copy of your information.</p>

                            <h5 class="mt-3 mb-2">c. Right to Rectification</h5>
                            <p>You can request correction of inaccurate, incomplete, or outdated personal data.</p>

                            <h5 class="mt-3 mb-2">d. Right to Erasure or Blocking</h5>
                            <p>You can request deletion or blocking of your personal data, subject to legal retention requirements.</p>

                            <h5 class="mt-3 mb-2">e. Right to Object</h5>
                            <p>You can object to the processing of your personal data for certain purposes.</p>

                            <h5 class="mt-3 mb-2">f. Right to Data Portability</h5>
                            <p>You can request to receive your personal data in a structured, commonly used format.</p>

                            <h5 class="mt-3 mb-2">g. Right to Damages</h5>
                            <p>You can claim compensation if your privacy rights are violated.</p>

                            <h5 class="mt-3 mb-2">h. Right to File a Complaint</h5>
                            <p>You can lodge a complaint with the National Privacy Commission (NPC) if you believe your data privacy rights are violated.</p>
                            
                            <div class="alert alert-info mt-3">
                                <strong>To exercise your rights:</strong> Contact us at 
                                <a href="mailto:<?php echo SUPPORT_EMAIL; ?>"><?php echo SUPPORT_EMAIL; ?></a> 
                                or fill out our Data Request Form.
                            </div>
                        </section>

                        <!-- Sharing of Data -->
                        <section class="mb-5">
                            <h3 class="mb-3">7. Sharing and Disclosure of Personal Data</h3>
                            <p>
                                <strong>We do NOT sell, trade, or rent your personal data to third parties.</strong> 
                                However, we may share your data in the following circumstances:
                            </p>
                            <ul>
                                <li><strong>Service Providers:</strong> With trusted third-party providers who assist us (e.g., payment processors, email services) under strict confidentiality agreements</li>
                                <li><strong>Legal Compliance:</strong> When required by law, court order, or regulatory authorities</li>
                                <li><strong>Business Transfer:</strong> If we merge or are acquired, your data may be transferred as part of the business assets</li>
                                <li><strong>With Consent:</strong> When you explicitly consent to share your data with third parties</li>
                                <li><strong>Public Safety:</strong> To protect the rights, privacy, safety of others or our company</li>
                            </ul>
                            <p class="text-muted mt-3">
                                Payment processing with Maya Payment Gateway is handled securely, and we do not store your full credit card information.
                            </p>
                        </section>

                        <!-- Cookies and Tracking -->
                        <section class="mb-5">
                            <h3 class="mb-3">8. Cookies and Tracking Technologies</h3>
                            <p>Our system uses session cookies to maintain your login session. These are temporary cookies that are deleted when you log out.</p>
                            <ul>
                                <li><strong>Session Cookies:</strong> Used to keep you logged in (expires after 30 minutes of inactivity)</li>
                                <li><strong>Security Cookies:</strong> Used to prevent unauthorized access and track suspicious activity</li>
                            </ul>
                            <p>We do NOT use persistent tracking cookies or third-party analytics that track your behavior across the web.</p>
                        </section>

                        <!-- Policy Changes -->
                        <section class="mb-5">
                            <h3 class="mb-3">9. Changes to This Privacy Policy</h3>
                            <p>
                                We may update this Privacy Policy from time to time to reflect changes in our practices or legal requirements. 
                                We will notify you of any significant changes by:
                            </p>
                            <ul>
                                <li>Posting the updated policy on this page with a new "Last Updated" date</li>
                                <li>Sending you an email notification</li>
                                <li>Requiring you to accept the updated policy on your next login (if material changes)</li>
                            </ul>
                            <p>Your continued use of our system after changes constitute your acceptance of the updated Privacy Policy.</p>
                        </section>

                        <!-- Contact Information -->
                        <section class="mb-5">
                            <h3 class="mb-3">10. Contact Us</h3>
                            <p>If you have any questions or concerns about this Privacy Policy or our data privacy practices, please contact us:</p>
                            <ul class="list-unstyled">
                                <li><strong>Email:</strong> <a href="mailto:<?php echo SUPPORT_EMAIL; ?>"><?php echo SUPPORT_EMAIL; ?></a></li>
                                <li><strong>Company:</strong> Level Up Fitness</li>
                                <li><strong>Address:</strong> Manila, Philippines</li>
                            </ul>

                            <div class="alert alert-warning mt-4">
                                <h6><i class="fas fa-info-circle"></i> National Privacy Commission</h6>
                                <p class="mb-2">
                                    If you wish to file a complaint with the regulatory authority, you can contact the 
                                    <strong>National Privacy Commission (NPC)</strong>:
                                </p>
                                <ul class="mb-0">
                                    <li><strong>Website:</strong> <a href="https://privacy.gov.ph" target="_blank">privacy.gov.ph</a></li>
                                    <li><strong>Email:</strong> complaints@privacy.gov.ph</li>
                                    <li><strong>Hotline:</strong> (+63 2) 5322 1322 Local 114 or 115</li>
                                    <li><strong>Address:</strong> 25th-27th Floors, The Upper Class Tower, Quezon Ave. Corner Scout Reyes Street, Quezon City 1103</li>
                                </ul>
                            </div>
                        </section>

                        <!-- Acknowledgment -->
                        <section>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <p class="mb-0">
                                        <strong>Data Protection Officer Contact:</strong> 
                                        For privacy-related inquiries, you may reach our Data Protection Officer at 
                                        <a href="mailto:<?php echo SUPPORT_EMAIL; ?>"><?php echo SUPPORT_EMAIL; ?></a>
                                    </p>
                                </div>
                            </div>
                        </section>
                    </div>

                    <hr class="my-5">
                    
                    <div class="d-flex gap-2">
                        <a href="<?php echo APP_URL; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Home
                        </a>
                        <a href="<?php echo APP_URL; ?>terms-conditions.php" class="btn btn-primary">
                            <i class="fas fa-file-contract"></i> View Terms & Conditions
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__FILE__) . '/includes/footer.php'; ?>
