# Admin Module Implementation & Fixes

## Dashboard - (DONE)

**URL:** http://127.0.0.1:8000/super-admin/dashboard

### Required Changes

1. **Total Users** and **Active Users** counts should exclude all Admin accounts.

---

## Users Management - (DONE)

**URL:** http://127.0.0.1:8000/super-admin/users

### Required Changes

1. Admin users should be hidden from the user list by default.
2. Add a new filter dropdown named **Role**.

   * The dropdown should display all existing roles from the database.
3. When filtering by the **Admin** role:

   * Display Admin users in the list.
   * Hide the **View** and **Notification** action icons, as they are not required for Admin accounts.

NOTE: **PLANS FILTERING NEED TO FIX AFTER SUBSCRIPTION MODULE FIX**
---

## User Details

**URL:** http://127.0.0.1:8000/super-admin/users/13

### 1. Ban User Feature

When the **Ban User** button is clicked, display a confirmation popup with the following fields:

* **Reason for Ban** (Textarea)
* **Send Email Notification** (Checkbox)

  * If checked, send an email to the user containing the ban reason.

#### Additional Requirements

* When a banned user attempts to log in, display:

  * Account status: **Banned**
  * The reason for the ban
* Admin should be able to reactivate a banned account at any time.

---

### 2. Face Scan Verification

#### Approval

When a face scan is approved:

* Send a notification to the frontend.
* Send an email notification to the user.

#### Rejection

When the **Reject** button is clicked, display a popup with the following fields:

* **Reason for Rejection** (Textarea)
* **Send Email Notification** (Checkbox)

  * If checked, send an email to the user containing the rejection reason.

#### Additional Requirements

* Rejected images should not be deleted.
* Store the rejection status and reason.
* Display the rejection reason in the UI.
* If the user logs in after a rejection:

  * Validate login credentials normally.
  * Redirect the user to the Face Verification page.
  * Display the reason why the previous verification was rejected.
* Once the user successfully submits a new face scan and it is approved, normal login access should be restored.
* All images should be served through Cloudflare variants:

  * Use small variants for thumbnails and list views.
  * Use larger variants where appropriate.

---

### 3. Photos Section

#### Rename Section

* Rename **Profile Photos** to **Photos**.

#### Photo Display Requirements

* If a primary photo exists, display a **Primary** label on that image.
* Integrate **Fancybox** for image preview.
* Display small Cloudflare image variants in the gallery.
* When a user clicks an image, open the full-size version in Fancybox.



## Subscription Plans - (DONE)

**URL:** http://127.0.0.1:8000/super-admin/subscription-plans

### Required Changes

1. Change sidebar and page heading text if exist **Subscriptions Plans** to **Plans**

2. Create a **Subscription Tier** management section under **Settings** with full CRUD functionality.

3. All **Subscription Tier** dropdowns throughout the system should load their options dynamically from the Subscription Tier master data.

4. Update **Sort Order** field to Subscription Tiers.

   * Tiers should be displayed in ascending order (1, 2, 3, 4, etc.).
   * Keep the implementation simple, as the business is expected to have only a small number of subscription tiers (typically 2–3 plans).

**NOTE** Subscription Tier CRUD LEFT


## Sales

**URL:** http://127.0.0.1:8000/super-admin/subscriptions

### Required Changes

1. Rename all occurrences of **"Subscriptions & Revenue"** and **"Subscriptions & Sales"** in the sidebar, page titles, and headings to **"Sales"**.

2. Add a date range filter:

   * **From Date**
   * **To Date**
   * When a date range is selected, the sales list and statistics should be filtered accordingly.

3. Below the date filter, display the following metrics based on the selected date range:

   * Total Revenue
   * Active Subscriptions
   * Total Sold

4. Keep the existing dashboard summary cards unchanged:

   * Total Revenue
   * This Month
   * Active Subscriptions
   * Total Sold

   These summary cards should always display overall system statistics and should not be affected by the date range filter.


## Approvals

**URL:** http://127.0.0.1:8000/super-admin/photos

### Required Changes

1. Rename all occurrences of **"Photo Moderation"** and **"Photo Moderation Queue"** in the sidebar, page titles, and headings to **"Approvals"**.

2. Add a new setting under the Settings page:

   * **Manual Photo Verification Required?** (Checkbox)

3. If **Manual Photo Verification Required** is enabled:

   * All photos uploaded from the frontend should be marked as **Pending**.
   * Photos must be reviewed and approved by an Admin through the **Approvals** section before becoming visible to other users.

4. If **Manual Photo Verification Required** is disabled:

   * Photos should be automatically approved upon upload from the frontend.
   * No manual review should be required.

5. Admins should be able to remove any photo at any time from the **Approvals** section.

6. When a photo is approved or removed, the corresponding status should be updated immediately and reflected across the application.

7. Sidebar after **Approvals** name show upapproved photo Count.



## Reports

**URL:** http://127.0.0.1:8000/super-admin/photos

### Required Changes

1. Rename all occurrences of **"Report Review Queue"** page titles, and headings to **"Reports"**.

2. Remove **Reviewed** and **Action Taken** from the heading.


## Notifications

**URL:** http://127.0.0.1:8000/super-admin/notifications

### Required Changes

1. Rename all occurrences of **"Notification History"** and **"Admin Notification History"** in the sidebar, page titles, and headings to **"Notifications"**.

2. By default, notifications should be sorted using the following order:

   * **Unread notifications first**
   * Within unread notifications, show the most recent notifications first (**Created Date DESC**)

   Example sorting:

   * Unread + Latest Date

3. Change sidebar Icon.


## Messages

**URL:** http://127.0.0.1:8000/super-admin/contact-messages

### Required Changes

1. Rename all occurrences of **"Contact Messages"**  in the sidebar, page titles, and headings to **"Messages"**.

2. By default, Messages should be sorted using the following order:

   * **Unread messages first**
   * Within unread messages, show the most recent messages first (**Created Date DESC**)

   Example sorting:

   * Unread + Latest Date

3. Remove **Replay icon** from list and **Replied** from status filter dropdown.

4. Rename and Make action inside status dropdown  **New** to **Unread**

4. Rename and Card section **New / Unread** to **Unread**



## CMS

**URL:** http://127.0.0.1:8000/super-admin/pages

### Required Changes

1. Rename all occurrences of **"Pages"** and **"Pages / CMS"** in the sidebar, page titles, and headings to **"CMS"**.

2. Add a **Create Page** feature with the same fields and functionality currently available in the **Edit Page** screen.

3. Remove **"Find Your Perfect Life Partner" (home_hero)** from the CMS module.

   * CMS should be used only for managing full pages.
   * Individual website sections (such as home page hero sections) should not be managed through the CMS.

4. Add a new checkbox field to both the **Create Page** and **Edit Page** forms:

   * **Show in Footer**
   * If enabled, the page should be displayed in the frontend footer navigation.
   * If disabled, the page should not appear in the footer.

5. Add a **Delete Page** option.

   * Admins should be able to permanently remove CMS pages that are no longer required.
   * Display a confirmation dialog before deletion.

6. CMS listing should display all created pages and provide the following actions:
   * View/Edit
   * Delete
   * Show in Footer status


## Site Settings

**URL:** http://127.0.0.1:8000/super-admin/settings

### Required Changes

#### Email OTP Verification

1. Add a new setting:

   * **Email OTP Verification Required** (Checkbox)

2. If enabled, the frontend registration flow should be:

   **Registration → Email OTP Verification → Face Verification (if enabled) → Account Access**

3. If Face Verification is disabled, the flow should be:

   **Registration → Email OTP Verification → Account Access**

4. If Email OTP Verification is disabled, the existing registration flow should remain unchanged.

5. All Images upload to cloudflare and show from cloudflare.

---

#### Social Media Links

1. Add a **LinkedIn** field under the **Social Media Links** section.

2. The LinkedIn URL should be:

   * Editable from the Admin panel.
   * Available for display on the frontend along with the existing social media links.



## Admin Layout

### Sidebar

1. In the sidebar footer section, change the label **"Admin User"** to **"Super Admin"**.
2. Sidebar toogle options.
3. Do not show logo on sidebar insted show **Site Name** From Database.

---

### Header

1. In the top-right corner of the header, display the name of the currently logged-in Admin user.

2. The displayed name should be dynamically loaded based on the authenticated Admin account.

   Example:

   * Admin
   * John Smith
   * Super Admin

