# FoodShare Platform - Functional Test Plan

## Overview
This document contains comprehensive functional test cases for the FoodShare Platform, a PHP + MySQL based leftover food sharing system connecting donors with receivers.

## Test Environment
- **Application**: FoodShare Platform (PHP + MySQL)
- **Browser**: Chrome/Firefox/Safari (latest versions)
- **Database**: MySQL 8.0+
- **Server**: Apache/Nginx with PHP 8.0+

---

# 1. USER REGISTRATION AND LOGIN TESTS

## 1.1 Donor Registration Tests

### TC_REG_001: Successful Donor Registration
**Description**: Verify that a new donor can successfully register with valid information
**Pre-conditions**: User is not logged in, registration form is accessible
**Steps**:
1. Navigate to registration page
2. Select "Donor" as user type
3. Enter valid username (6-20 characters, alphanumeric)
4. Enter valid email address (proper format)
5. Enter password (minimum 8 characters with special character)
6. Confirm password
7. Enter organization name
8. Enter contact number
9. Click "Register" button
**Expected Result**:
- Registration successful message displayed
- User redirected to login page
- User account created in database with role='donor'
- Confirmation email sent (if email functionality enabled)

### TC_REG_002: Receiver Registration
**Description**: Verify that a new receiver can successfully register
**Pre-conditions**: User is not logged in, registration form is accessible
**Steps**:
1. Navigate to registration page
2. Select "Receiver" as user type
3. Enter valid username (6-20 characters, alphanumeric)
4. Enter valid email address
5. Enter password (minimum 8 characters)
6. Confirm password
7. Enter organization name
8. Enter contact number
9. Click "Register" button
**Expected Result**:
- Registration successful message displayed
- User redirected to login page
- User account created in database with role='receiver'

### TC_REG_003: Registration with Existing Username
**Description**: Verify system handles duplicate username gracefully
**Pre-conditions**: A user with username "testuser" already exists
**Steps**:
1. Navigate to registration page
2. Enter "testuser" as username
3. Enter valid email and password
4. Submit registration form
**Expected Result**:
- Error message: "Username already exists"
- User remains on registration page
- No new account created

### TC_REG_004: Registration with Invalid Email Format
**Description**: Verify email validation works correctly
**Steps**:
1. Navigate to registration page
2. Enter valid username
3. Enter invalid email (e.g., "invalid-email")
4. Enter valid password
5. Submit registration form
**Expected Result**:
- Error message: "Please enter a valid email address"
- User remains on registration page

### TC_REG_005: Registration with Weak Password
**Description**: Verify password strength validation
**Steps**:
1. Navigate to registration page
2. Enter valid username and email
3. Enter weak password (e.g., "123")
4. Submit registration form
**Expected Result**:
- Error message: "Password must be at least 8 characters long and contain special characters"
- User remains on registration page

## 1.2 Login Tests

### TC_LOGIN_001: Successful Donor Login
**Description**: Verify donor can login with correct credentials
**Pre-conditions**: Donor account exists and is active
**Steps**:
1. Navigate to login page
2. Enter valid donor username/email
3. Enter correct password
4. Click "Login" button
**Expected Result**:
- Login successful
- User redirected to dashboard/homepage
- Session variables set (user_id, username, role='donor')
- Navigation shows donor-specific options

### TC_LOGIN_002: Successful Receiver Login
**Description**: Verify receiver can login with correct credentials
**Pre-conditions**: Receiver account exists and is active
**Steps**:
1. Navigate to login page
2. Enter valid receiver username/email
3. Enter correct password
4. Click "Login" button
**Expected Result**:
- Login successful
- User redirected to dashboard/homepage
- Session variables set (user_id, username, role='receiver')
- Navigation shows receiver-specific options

### TC_LOGIN_003: Invalid Credentials
**Description**: Verify system handles invalid login attempts
**Steps**:
1. Navigate to login page
2. Enter invalid username/email
3. Enter incorrect password
4. Click "Login" button
**Expected Result**:
- Error message: "Invalid username or password"
- User remains on login page
- No session variables set

### TC_LOGIN_004: Session Management
**Description**: Verify session handling and timeout
**Steps**:
1. Login as donor
2. Wait for session timeout (or manually expire session)
3. Try to access protected page
**Expected Result**:
- User automatically redirected to login page
- Session variables cleared
- Access denied to protected resources

---

# 2. DONOR FOOD POSTING TESTS

## 2.1 Food Post Creation

### TC_POST_001: Successful Food Post Creation
**Description**: Verify donor can create a food post with all required fields
**Pre-conditions**: User logged in as donor
**Steps**:
1. Navigate to "Offer Food" section
2. Enter food name: "Fresh Pizza Slices"
3. Enter quantity: "15"
4. Select food type: "Cooked Meal"
5. Enter pickup location: "Hotel Main Kitchen - Back Door"
6. Set expiration datetime: 2 hours from now
7. Select dietary options: Vegetarian, Halal
8. Enter contact number: "+1234567890"
9. Click "Offer Food" button
**Expected Result**:
- Success message: "Food posted successfully"
- Food post created in database with status='available'
- Post linked to donor's user_id
- User redirected to "My Posts" page or dashboard

### TC_POST_002: Food Post with Image Upload
**Description**: Verify image upload functionality
**Pre-conditions**: User logged in as donor, valid image file available
**Steps**:
1. Navigate to food posting form
2. Fill all required fields
3. Upload valid image file (JPG/PNG, < 5MB)
4. Submit form
**Expected Result**:
- Image uploaded successfully to /uploads folder
- Unique filename generated
- Image path stored in database
- Success message displayed

### TC_POST_003: Food Post Validation - Empty Fields
**Description**: Verify required field validation
**Steps**:
1. Navigate to food posting form
2. Leave food name empty
3. Enter quantity: "10"
4. Submit form
**Expected Result**:
- Error message: "Food name is required"
- Form not submitted
- User remains on form page

### TC_POST_004: Food Post with Past Expiration Date
**Description**: Verify expiration date validation
**Steps**:
1. Navigate to food posting form
2. Fill all required fields
3. Set expiration datetime to past time
4. Submit form
**Expected Result**:
- Error message: "Expiration date must be in the future"
- Form not submitted
- User remains on form page

### TC_POST_005: Image Upload Validation - Invalid File Type
**Description**: Verify image file type validation
**Steps**:
1. Navigate to food posting form
2. Fill required fields
3. Upload invalid file type (e.g., PDF)
4. Submit form
**Expected Result**:
- Error message: "Only JPG, PNG, and GIF files are allowed"
- Form not submitted
- User remains on form page

### TC_POST_006: Image Upload Validation - File Size Limit
**Description**: Verify file size validation
**Steps**:
1. Navigate to food posting form
2. Fill required fields
3. Upload image larger than 5MB
4. Submit form
**Expected Result**:
- Error message: "File size must be less than 5MB"
- Form not submitted
- User remains on form page

---

# 3. RECEIVER FOOD CLAIMING TESTS

## 3.1 Food Claiming Process

### TC_CLAIM_001: Successful Food Claim
**Description**: Verify receiver can successfully claim available food
**Pre-conditions**: User logged in as receiver, available food posts exist
**Steps**:
1. Navigate to food listings page
2. View available food posts
3. Click "Claim Food" button on an available post
4. Confirm claim action
**Expected Result**:
- Success message: "Food claimed successfully"
- Food post status changed to 'claimed' in database
- Pickup record created in pickups table
- User redirected to "My Claimed Foods" page

### TC_CLAIM_002: Claim Expired Food
**Description**: Verify system prevents claiming expired food
**Pre-conditions**: Food post exists with past expiration date
**Steps**:
1. Login as receiver
2. Attempt to claim expired food post
**Expected Result**:
- Error message: "This food item has expired and is no longer available"
- Claim not processed
- Food remains available for other users

### TC_CLAIM_003: Duplicate Claim Prevention
**Description**: Verify receiver cannot claim same food twice
**Pre-conditions**: Receiver has already claimed a food post
**Steps**:
1. Login as receiver
2. Attempt to claim the same food post again
**Expected Result**:
- Error message: "You have already claimed this food item"
- No duplicate pickup record created
- Original claim remains intact

### TC_CLAIM_004: Claim Unavailable Food
**Description**: Verify system prevents claiming already claimed food
**Pre-conditions**: Food post has been claimed by another user
**Steps**:
1. Login as receiver
2. Attempt to claim food that shows as "claimed"
**Expected Result**:
- Error message: "Food is no longer available"
- Claim not processed
- User redirected to food listings

## 3.2 My Claimed Foods Page

### TC_CLAIM_005: View Active Claims
**Description**: Verify receiver can view their active food claims
**Pre-conditions**: Receiver has active food claims
**Steps**:
1. Login as receiver
2. Navigate to "My Claimed Foods" page
3. View active claims section
**Expected Result**:
- Active claims displayed with status "Scheduled"
- Food details shown (name, quantity, donor, pickup location)
- Expiration time displayed if available
- Scheduled pickup time shown

### TC_CLAIM_006: View Past Claims
**Description**: Verify receiver can view completed/cancelled claims
**Pre-conditions**: Receiver has completed or cancelled claims
**Steps**:
1. Login as receiver
2. Navigate to "My Claimed Foods" page
3. View past claims section
**Expected Result**:
- Past claims displayed with appropriate status badges
- Completed claims show "Completed" status
- Cancelled claims show "Cancelled" status
- Historical data preserved

---

# 4. NGO ACCOUNT VERIFICATION TESTS

### TC_NGO_001: NGO Registration Request
**Description**: Verify NGO can request account verification
**Pre-conditions**: User registered as receiver, logged in
**Steps**:
1. Login as receiver
2. Navigate to profile page
3. Request NGO verification
4. Upload required documents
5. Submit verification request
**Expected Result**:
- Verification request submitted successfully
- Request status set to "pending"
- Admin notification sent (if notification system enabled)

### TC_NGO_002: Admin Approval Process
**Description**: Verify admin can approve NGO verification
**Pre-conditions**: Admin logged in, pending NGO verification request exists
**Steps**:
1. Login as admin
2. Navigate to admin panel
3. Review NGO verification request
4. Approve the request
**Expected Result**:
- NGO status updated to "verified"
- User role updated to "ngo"
- Confirmation sent to NGO
- NGO-specific features unlocked

### TC_NGO_003: Admin Rejection Process
**Description**: Verify admin can reject NGO verification
**Pre-conditions**: Admin logged in, pending NGO verification request exists
**Steps**:
1. Login as admin
2. Navigate to admin panel
3. Review NGO verification request
4. Reject the request with reason
**Expected Result**:
- NGO status remains "receiver"
- Rejection notification sent to user
- Reason for rejection provided

---

# 5. FOOD POST EXPIRY HANDLING TESTS

### TC_EXPIRY_001: Automatic Expiry Check
**Description**: Verify system automatically marks expired posts as unavailable
**Pre-conditions**: Food posts exist with past expiration dates
**Steps**:
1. Wait for food posts to expire (or manually set past dates)
2. Run expiry check process
3. Check food post status
**Expected Result**:
- Expired food posts automatically marked as "expired"
- Posts no longer appear in available listings
- Notifications sent to affected users (if applicable)

### TC_EXPIRY_002: Manual Expiry Check
**Description**: Verify admin can manually expire food posts
**Pre-conditions**: Admin logged in, active food posts exist
**Steps**:
1. Login as admin
2. Navigate to admin panel
3. Select food post to expire
4. Manually expire the post
**Expected Result**:
- Food post status changed to "expired"
- Post removed from available listings
- Notification sent to donor

### TC_EXPIRY_003: Expiry Notification
**Description**: Verify expiry notifications are sent correctly
**Pre-conditions**: Food posts approaching expiration, notification system enabled
**Steps**:
1. Create food post with expiration time 1 hour from now
2. Wait for notification trigger
3. Check notification delivery
**Expected Result**:
- Expiration warning sent to donor
- Receiver notified if they claimed the food
- Appropriate notification message displayed

---

# 6. PICKUP SCHEDULING TESTS

### TC_PICKUP_001: Automatic Pickup Scheduling
**Description**: Verify pickup record created with correct scheduling
**Pre-conditions**: Receiver successfully claims food
**Steps**:
1. Login as receiver
2. Claim available food post
3. Check pickup record in database
**Expected Result**:
- Pickup record created in pickups table
- scheduled_at set to current timestamp
- status set to "scheduled"
- Links to correct post_id and receiver_id

### TC_PICKUP_002: Pickup Status Update
**Description**: Verify pickup status can be updated
**Pre-conditions**: Pickup record exists with "scheduled" status
**Steps**:
1. Login as donor or receiver
2. Update pickup status to "completed"
3. Verify status change
**Expected Result**:
- Pickup status updated to "completed"
- Timestamp recorded
- Status reflected in "My Claimed Foods" page

### TC_PICKUP_003: Pickup Cancellation
**Description**: Verify pickup can be cancelled
**Pre-conditions**: Pickup record exists with "scheduled" status
**Steps**:
1. Login as receiver
2. Cancel the pickup
3. Confirm cancellation
**Expected Result**:
- Pickup status changed to "cancelled"
- Food post status changed back to "available"
- Cancellation notification sent to donor

---

# 7. SESSION MANAGEMENT TESTS

### TC_SESSION_001: Session Persistence
**Description**: Verify user session persists across page navigation
**Pre-conditions**: User successfully logged in
**Steps**:
1. Login as donor/receiver
2. Navigate to different pages
3. Check session variables
**Expected Result**:
- Session variables maintained (user_id, username, role)
- User remains logged in
- Navigation reflects user role

### TC_SESSION_002: Session Timeout
**Description**: Verify session expires after inactivity
**Pre-conditions**: User logged in, session timeout configured
**Steps**:
1. Login as user
2. Wait for session timeout period
3. Try to access protected page
**Expected Result**:
- Session automatically expired
- User redirected to login page
- Session variables cleared

### TC_SESSION_003: Concurrent Session Handling
**Description**: Verify handling of multiple concurrent sessions
**Pre-conditions**: User logs in from multiple browsers/devices
**Steps**:
1. Login from browser 1
2. Login from browser 2
3. Perform actions from both sessions
**Expected Result**:
- Both sessions remain active
- Actions from both sessions processed correctly
- No session conflicts or data corruption

### TC_SESSION_004: Session Security
**Description**: Verify session security measures
**Steps**:
1. Login as user
2. Attempt to access session data directly
3. Try session hijacking techniques
**Expected Result**:
- Session data properly secured
- Session hijacking attempts prevented
- Secure session cookies used

---

# 8. IMAGE UPLOAD VALIDATION TESTS

### TC_UPLOAD_001: Valid Image Upload
**Description**: Verify successful upload of valid image files
**Pre-conditions**: Food posting form accessible, valid image file available
**Steps**:
1. Navigate to food posting form
2. Select valid image file (JPG, < 2MB)
3. Submit form
**Expected Result**:
- Image uploaded successfully
- File stored in /uploads directory
- Unique filename generated
- Image path stored in database

### TC_UPLOAD_002: Invalid File Type
**Description**: Verify rejection of invalid file types
**Steps**:
1. Navigate to food posting form
2. Attempt to upload non-image file (PDF, DOC)
3. Submit form
**Expected Result**:
- Upload rejected
- Error message: "Only image files are allowed"
- Form submission prevented

### TC_UPLOAD_003: File Size Limit
**Description**: Verify file size validation
**Steps**:
1. Navigate to food posting form
2. Attempt to upload image > 5MB
3. Submit form
**Expected Result**:
- Upload rejected
- Error message: "File size must be less than 5MB"
- Form submission prevented

### TC_UPLOAD_004: Image Security Scan
**Description**: Verify uploaded images are scanned for security
**Steps**:
1. Navigate to food posting form
2. Upload image with malicious content (if possible)
3. Submit form
**Expected Result**:
- Malicious content detected and rejected
- Upload prevented
- Security log entry created

---

# Test Execution Summary

## Test Environment Setup
- Database: MySQL with test data
- Application: Deployed on test server
- Test Users: Pre-created donor, receiver, and admin accounts

## Test Data Requirements
- Sample food posts with various statuses
- Users with different roles and verification statuses
- Test images of various formats and sizes

## Expected Test Results
- **Pass Rate**: >95% for all critical functionality
- **Critical Defects**: 0
- **Performance**: All operations complete within 3 seconds

## Defect Reporting
All defects should be logged with:
- Severity (Critical, High, Medium, Low)
- Steps to reproduce
- Expected vs Actual results
- Screenshots (where applicable)
- Browser/Environment details
