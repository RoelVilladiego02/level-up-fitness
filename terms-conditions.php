<?php
/**
 * Terms & Conditions Page
 * Level Up Fitness - Gym Management System
 * Philippines-Compliant Terms
 */

require_once dirname(__FILE__) . '/config/config.php';
require_once dirname(__FILE__) . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body p-5">
                    <h1 class="mb-3"><i class="fas fa-file-contract"></i> Terms & Conditions</h1>
                    <p class="text-muted mb-4">Last Updated: May 2026</p>
                    
                    <div class="terms-content">
                        <!-- Acceptance -->
                        <section class="mb-5">
                            <h3 class="mb-3">1. Acceptance of Terms</h3>
                            <p>
                                By accessing and using the Level Up Fitness Gym Management System ("the System"), you hereby agree to be bound by 
                                these Terms & Conditions. If you do not agree to these terms, you are prohibited from using or accessing this System. 
                                We reserve the right to modify these terms at any time, and your continued use signifies your acceptance of any changes.
                            </p>
                        </section>

                        <!-- Use License -->
                        <section class="mb-5">
                            <h3 class="mb-3">2. License and Use of System</h3>
                            <p>
                                Level Up Fitness grants you a limited, non-exclusive, non-transferable license to use the System solely for the purposes 
                                of managing your gym membership, training sessions, and related services. You may not:
                            </p>
                            <ul>
                                <li>Copy, duplicate, or reproduce any content or functionality of the System</li>
                                <li>Attempt to reverse-engineer, hack, or breach the security of the System</li>
                                <li>Use the System for any illegal, fraudulent, or unauthorized purposes</li>
                                <li>Interfere with or disrupt the operation of the System or its servers</li>
                                <li>Share your login credentials with unauthorized persons</li>
                                <li>Collect or harvest personal data of other users without consent</li>
                                <li>Use automated bots or scripts to access or manipulate the System</li>
                            </ul>
                        </section>

                        <!-- User Responsibilities -->
                        <section class="mb-5">
                            <h3 class="mb-3">3. User Responsibilities and Conduct</h3>
                            
                            <h5 class="mt-3 mb-2">A. Member Responsibilities:</h5>
                            <ul>
                                <li>Provide accurate and truthful information during account creation and profile updates</li>
                                <li>Maintain confidentiality of your login credentials and password</li>
                                <li>Notify us immediately of any unauthorized access to your account</li>
                                <li>Comply with all gym rules, health and safety regulations, and facility guidelines</li>
                                <li>Use equipment properly and responsibly</li>
                                <li>Respect other members' and staff members' rights and privacy</li>
                                <li>Dress appropriately according to gym standards</li>
                                <li>Not conduct any illegal, harassing, or disruptive activities at the facility</li>
                                <li>Report any facility hazards or safety concerns to management immediately</li>
                            </ul>

                            <h5 class="mt-3 mb-2">B. Trainer Responsibilities:</h5>
                            <ul>
                                <li>Provide professional and courteous service to all members</li>
                                <li>Follow all gym policies and safety protocols</li>
                                <li>Maintain confidentiality regarding members' personal information and health data</li>
                                <li>Not provide medical advice; refer members to qualified healthcare professionals when necessary</li>
                                <li>Update your availability and schedule regularly</li>
                                <li>Respect professional boundaries with members</li>
                            </ul>
                        </section>

                        <!-- Health and Safety Disclaimer -->
                        <section class="mb-5">
                            <h3 class="mb-3">4. Health and Safety Disclaimer</h3>
                            <p class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> 
                                <strong>Important Health Disclaimer:</strong>
                            </p>
                            <ul>
                                <li>
                                    <strong>Medical Advice:</strong> Information and recommendations provided by trainers are NOT medical advice. 
                                    Consult with a qualified physician before starting any fitness program, especially if you have pre-existing medical conditions.
                                </li>
                                <li>
                                    <strong>Assumption of Risk:</strong> You acknowledge the inherent risks associated with physical exercise and 
                                    agree that you engage in activities at your own risk. Level Up Fitness is not liable for injuries sustained while 
                                    using the facility or participating in programs.
                                </li>
                                <li>
                                    <strong>Medical Clearance:</strong> Members are encouraged to obtain medical clearance from their physician 
                                    before participating in fitness programs.
                                </li>
                                <li>
                                    <strong>Personal Responsibility:</strong> You are responsible for listening to your body and stopping if you 
                                    experience pain, discomfort, or distress.
                                </li>
                            </ul>
                        </section>

                        <!-- Membership Terms -->
                        <section class="mb-5">
                            <h3 class="mb-3">5. Membership Terms and Conditions</h3>
                            
                            <h5 class="mt-3 mb-2">A. Membership Types:</h5>
                            <p>
                                Memberships are available as:
                            </p>
                            <ul>
                                <li><strong>Monthly:</strong> Billed monthly, renewable automatically unless cancelled</li>
                                <li><strong>Quarterly:</strong> 3-month term with savings versus monthly billing</li>
                                <li><strong>Annual:</strong> 12-month term with best value pricing</li>
                            </ul>

                            <h5 class="mt-3 mb-2">B. Membership Activation:</h5>
                            <ul>
                                <li>Memberships are activated upon successful payment</li>
                                <li>Members must verify their email address to activate their account</li>
                                <li>Access to the facility begins on the activation date</li>
                            </ul>

                            <h5 class="mt-3 mb-2">C. Membership Expiration:</h5>
                            <ul>
                                <li>Membership expiration dates are clearly displayed in your account dashboard</li>
                                <li>Access to the facility terminates upon expiration unless renewed</li>
                                <li>We will send email reminders 7 days before expiration</li>
                            </ul>

                            <h5 class="mt-3 mb-2">D. Membership Freeze/Hold:</h5>
                            <ul>
                                <li>Temporary membership freeze may be available for medical or personal reasons (contact management)</li>
                                <li>Freeze periods are subject to terms agreed upon with management</li>
                            </ul>
                        </section>

                        <!-- Payment Terms -->
                        <section class="mb-5">
                            <h3 class="mb-3">6. Payment Terms and Billing</h3>
                            
                            <h5 class="mt-3 mb-2">A. Payment Methods:</h5>
                            <ul>
                                <li>Credit/Debit Card (via Maya Payment Gateway)</li>
                                <li>Cash (in-gym payment)</li>
                                <li>Bank Transfer</li>
                                <li>GCash/e-wallet transfers</li>
                            </ul>

                            <h5 class="mt-3 mb-2">B. Payment Processing:</h5>
                            <ul>
                                <li>Payments are processed securely through authorized payment gateways</li>
                                <li>All payment information is encrypted and protected</li>
                                <li>Payment receipts and invoices are available in your account</li>
                                <li>We do not store full credit card details on our servers</li>
                            </ul>

                            <h5 class="mt-3 mb-2">C. Invoicing and Records:</h5>
                            <ul>
                                <li>Digital invoices are automatically generated for all transactions</li>
                                <li>Invoices are available for download from your account dashboard</li>
                                <li>Payment records are retained per Philippine tax regulations (5 years)</li>
                            </ul>

                            <h5 class="mt-3 mb-2">D. Late Payment Policy:</h5>
                            <ul>
                                <li>Membership access may be suspended if payment is overdue by 30 days</li>
                                <li>A notice will be sent before suspension</li>
                                <li>Payment arrangement requests should be made to management</li>
                            </ul>

                            <h5 class="mt-3 mb-2">E. Refund and Cancellation Policy:</h5>
                            <ul>
                                <li><strong>Cancellation:</strong> Memberships may be cancelled with 7 days written notice</li>
                                <li><strong>Refunds:</strong> Refunds are issued on a pro-rata basis for unused membership periods</li>
                                <li><strong>Processing Time:</strong> Refunds are processed within 10-15 business days</li>
                                <li><strong>Cancellation Fee:</strong> A ₱500 administrative cancellation fee applies</li>
                                <li><strong>Early Termination:</strong> Annual memberships cancelled before 6 months incur 20% early termination fee</li>
                            </ul>
                        </section>

                        <!-- Classes and Sessions -->
                        <section class="mb-5">
                            <h3 class="mb-3">7. Classes and Training Sessions</h3>
                            <ul>
                                <li>
                                    <strong>Reservation:</strong> Members may reserve training sessions or classes through the system. 
                                    Reservations must be cancelled at least 24 hours in advance.
                                </li>
                                <li>
                                    <strong>No-Show Policy:</strong> Failure to cancel within 24 hours or not showing up results in forfeiture 
                                    of the session (no refund). Consecutive no-shows may result in booking privileges being temporarily suspended.
                                </li>
                                <li>
                                    <strong>Schedule Changes:</strong> Level Up Fitness reserves the right to modify class schedules and cancel 
                                    sessions with 48 hours notice.
                                </li>
                                <li>
                                    <strong>Trainer Availability:</strong> Personal training sessions must be booked through the system. 
                                    Session availability depends on trainer scheduling.
                                </li>
                                <li>
                                    <strong>Cancellation by Facility:</strong> If Level Up Fitness cancels a session, members will receive 
                                    a full credit or alternative session booking.
                                </li>
                            </ul>
                        </section>

                        <!-- Intellectual Property -->
                        <section class="mb-5">
                            <h3 class="mb-3">8. Intellectual Property Rights</h3>
                            <p>
                                All content, including text, graphics, logos, images, audio, and video on the Level Up Fitness System 
                                are the property of Level Up Fitness or its content suppliers and are protected by Philippine and international 
                                copyright laws. You are granted a limited license to view and use the content for personal use only. 
                                Reproduction, modification, or distribution is prohibited without written permission.
                            </p>
                        </section>

                        <!-- Limitation of Liability -->
                        <section class="mb-5">
                            <h3 class="mb-3">9. Limitation of Liability</h3>
                            <div class="alert alert-warning">
                                <p class="mb-0">
                                    <strong>TO THE MAXIMUM EXTENT PERMITTED BY PHILIPPINE LAW:</strong> Level Up Fitness shall not be liable for 
                                    any indirect, incidental, special, consequential, or punitive damages arising from your use of or inability to 
                                    use the System or services, including but not limited to:
                                </p>
                            </div>
                            <ul>
                                <li>Loss of data, information, or records</li>
                                <li>Interruption of service or access to the System</li>
                                <li>Injuries sustained during fitness activities</li>
                                <li>Third-party claims related to your use of the System</li>
                                <li>Actions or omissions of other members or staff</li>
                            </ul>
                            <p class="mt-3">
                                <strong>Except as required by law (such as personal injury or willful misconduct), 
                                Level Up Fitness' total liability shall not exceed the amount paid for membership in the preceding 12 months.</strong>
                            </p>
                        </section>

                        <!-- Indemnification -->
                        <section class="mb-5">
                            <h3 class="mb-3">10. Indemnification</h3>
                            <p>
                                You agree to indemnify and hold harmless Level Up Fitness, its owners, employees, and agents from any claims, 
                                damages, losses, liabilities, and expenses (including attorney's fees) arising from:
                            </p>
                            <ul>
                                <li>Your violation of these Terms & Conditions</li>
                                <li>Your misuse of the System</li>
                                <li>Injuries or damages you cause to others or property</li>
                                <li>Violation of applicable Philippine laws</li>
                                <li>Infringement of third-party rights through your actions</li>
                            </ul>
                        </section>

                        <!-- Dispute Resolution -->
                        <section class="mb-5">
                            <h3 class="mb-3">11. Dispute Resolution</h3>
                            <p>
                                These Terms & Conditions shall be governed by and construed in accordance with the laws of the Republic of the Philippines, 
                                without regard to its conflict of law provisions.
                            </p>
                            <p>
                                <strong>Dispute Resolution Process:</strong>
                            </p>
                            <ol>
                                <li>
                                    <strong>Good Faith Negotiation:</strong> In case of disputes, both parties agree to attempt resolution through 
                                    good faith negotiation within 15 days.
                                </li>
                                <li>
                                    <strong>Escalation to Management:</strong> If unresolved, escalate to Level Up Fitness management for mediation.
                                </li>
                                <li>
                                    <strong>Legal Remedies:</strong> Unresolved disputes may be brought before the appropriate Philippine courts.
                                </li>
                            </ol>
                        </section>

                        <!-- Termination of Access -->
                        <section class="mb-5">
                            <h3 class="mb-3">12. Termination of Access</h3>
                            <p>
                                Level Up Fitness reserves the right to terminate your access to the System immediately, with or without cause, if you:
                            </p>
                            <ul>
                                <li>Violate these Terms & Conditions</li>
                                <li>Engage in illegal or fraudulent activity</li>
                                <li>Harass or threaten other members or staff</li>
                                <li>Attempt to breach System security</li>
                                <li>Fail to pay membership fees</li>
                                <li>Engage in conduct that endangers the safety of others</li>
                            </ul>
                            <p>Upon termination, your access to all features will be revoked, though your data will be retained per our Privacy Policy.</p>
                        </section>

                        <!-- Severability -->
                        <section class="mb-5">
                            <h3 class="mb-3">13. Severability</h3>
                            <p>
                                If any provision of these Terms & Conditions is found to be invalid or unenforceable under Philippine law, 
                                such provision shall be modified to the minimum extent necessary to make it valid, or if not possible, severed. 
                                The remaining provisions shall continue in full force and effect.
                            </p>
                        </section>

                        <!-- Contact -->
                        <section class="mb-5">
                            <h3 class="mb-3">14. Contact Information</h3>
                            <p>For questions or concerns regarding these Terms & Conditions, please contact:</p>
                            <ul class="list-unstyled">
                                <li><strong>Email:</strong> <a href="mailto:<?php echo SUPPORT_EMAIL; ?>"><?php echo SUPPORT_EMAIL; ?></a></li>
                                <li><strong>Company:</strong> Level Up Fitness</li>
                                <li><strong>Address:</strong> Manila, Philippines</li>
                            </ul>
                        </section>

                        <!-- Acknowledgment -->
                        <section>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <p class="mb-2">
                                        <strong>By using Level Up Fitness System, you acknowledge that you have read, understood, and agree to be bound by these Terms & Conditions and our Privacy Policy.</strong>
                                    </p>
                                    <p class="mb-0 small text-muted">
                                        If you do not agree, you must discontinue use of the System immediately.
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
                        <a href="<?php echo APP_URL; ?>privacy-policy.php" class="btn btn-primary">
                            <i class="fas fa-lock"></i> View Privacy Policy
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__FILE__) . '/includes/footer.php'; ?>
