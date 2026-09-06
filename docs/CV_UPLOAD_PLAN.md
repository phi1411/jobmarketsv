# CV Upload & Application Snapshot Plan

## Purpose

Add a secure, practical CV-upload flow for students without changing the existing job-search, application-status, company moderation, or authentication flows.

The MVP supports **one active CV per student**. The active CV is a PDF uploaded by its owner. When the student applies, the server records the selected CV file as the application snapshot. A later replacement or deletion of the active CV must not change the CV already associated with an application.

## Product decisions

| Decision | MVP policy |
| --- | --- |
| Number of active CVs | One per student |
| Accepted format | PDF only |
| Maximum size | 5 MB |
| Storage visibility | Private; never under the public document root |
| Apply behavior | The backend uses the authenticated student's active CV only; it does not accept a client-supplied URL or file path |
| Employer access | Only an employer who owns the job for that application can view or download the snapshot |
| Student access | Only the CV owner can view, download, replace, or delete the active CV |
| Replacement | New upload becomes the active CV; old application snapshots remain available |
| Deletion | Removes only the active-profile reference and its unreferenced stored file; never deletes a snapshot used by an application |

## Explicit non-goals

- Multiple CV versions or choosing among several CVs.
- DOC/DOCX, image, ZIP, or arbitrary file uploads.
- CV parsing, OCR, AI scoring, CV builder, virus scanning service, or public CV search.
- Public/object-storage URLs, direct file-system paths in API responses, or client-provided `cv_url` / `resume` / `cv_url_snapshot` values.
- Changes to application status logic, employer notes, Google authentication, job search, or visual-polish tasks.

## Security and privacy rules

1. Store files outside `public/`; files are delivered only through an authorization-checked controller endpoint.
2. Authenticate before every upload, replacement, deletion, view, and download request.
3. Derive the student owner from the JWT. Never accept `user_id`, profile ID, upload path, or storage key from the request.
4. Validate extension, upload error, file size, and server-side MIME detection. Accept `application/pdf` only; do not trust the browser MIME type or filename.
5. Generate a random server-side storage name. Preserve the original filename only as safely escaped metadata for the owner/download response.
6. Do not return an absolute server path, private storage key, or document URL in public-profile responses or unrelated application list responses.
7. Resolve an application snapshot from the database and verify ownership before streaming it. An application ID alone must not grant access.
8. Delete physical files only after confirming that no current profile or application snapshot references them. Deletion failure must not make the database reference inconsistent.
9. Reject application submission with a clear validation error when the student has no active CV. The UI should guide them to upload one first.
10. Do not log document contents, signed/private URLs, or arbitrary upload payloads.

## Data model direction

The existing project persists a profile CV as `student_profiles.cv_url` and the application snapshot as `applications.resume` / `cv_url_snapshot` compatibility aliases. Preserve existing migration history and use one forward migration only.

Add durable, private-file metadata needed by this MVP to the student-profile record and application record, using project naming conventions after inspecting the actual base schema:

- opaque server-generated storage key/path;
- original display filename;
- MIME type;
- byte size;
- profile active-CV timestamps as useful for display;
- application snapshot metadata sufficient to serve the exact file after a profile CV changes.

The implementation may retain legacy URL columns temporarily for compatibility, but must stop using arbitrary external URLs as the source of an application CV. New records must use server-owned private file references. Existing URL-only CVs must remain readable in profile data until a separate data-migration decision; they must not bypass the new upload validation or private access policy.

## Delivery order

### CV-P0-01 — Private PDF upload foundation

Current problem:
Student profiles accept `cv_url` values from the client, while the application service can also accept `cv_url_snapshot` or `resume` from the application request. There is no multipart upload, file validation, or private storage boundary.

Desired result:
A signed-in student can upload, replace, inspect, and delete one active PDF CV through authenticated endpoints. The backend stores an opaque private reference and validated metadata.

Affected pages/components:

- `app/Routes/api.php`
- `app/Http/Controllers/ProfileController.php` or a narrowly scoped CV controller
- Profile domain service/entity/repository
- One new forward migration under `app/Migrations/`
- A storage service/helper and private storage directory configuration
- `app/Views/student/profile.php` and its directly associated JavaScript

Implementation scope:

- Add authenticated student-only endpoints for CV metadata, multipart PDF upload/replace, and delete.
- Add a private storage location outside `public/` and ensure it is created safely/configured for deployment.
- Add the minimum forward schema migration and repository support for secure file metadata.
- Make profile API return only safe owner-facing CV metadata, never a raw storage path.
- Replace the profile's external URL entry UX with upload, current-file, replace, and delete states.
- Return useful `422` validation errors for missing file, non-PDF, invalid MIME, upload failure, and files over 5 MB.

Do NOT change:

- Job, application-status, notification, employer, admin, auth, or Google OAuth behavior.
- Public profile response shape except that it must continue to omit CV data.
- Existing legacy data via destructive migration or filesystem cleanup.

Acceptance criteria:

- A guest and a company/admin account cannot upload, replace, inspect, or delete a student's CV.
- A student cannot affect another student's active CV by sending an ID, path, or `user_id`.
- A valid PDF up to 5 MB is stored outside `public/` under a server-generated opaque name.
- A renamed non-PDF, invalid MIME file, oversized file, and upload error are rejected by backend validation.
- Replacing a CV changes only the current student's active CV and leaves the previous file intact until snapshot-reference rules are implemented in CV-P0-02.
- The student profile UI shows loading, empty, uploading, success, and backend-error states.
- Tests use the isolated testing database and a disposable test-only storage directory.

Estimated size: Large

### CV-P0-02 — Server-owned application snapshot and protected delivery

Current problem:
`ApplicationService::apply()` starts from the profile `cv_url`, but lets a request override it through `cv_url_snapshot` or `resume`. The stored value can be an arbitrary external URL and there is no protected way to serve a stored CV.

Desired result:
Every new application uses the authenticated student's current private CV snapshot selected by the server. Only the student owner or the owner of the application job can retrieve that exact snapshot.

Affected pages/components:

- `app/Domain/ApplicationService.php`
- Application entity/repository and the migration created in CV-P0-01
- `app/Http/Controllers/ApplicationController.php` or narrowly scoped file-delivery controller
- `app/Routes/api.php`
- Student apply view/script and company application view/script

Implementation scope:

- Remove acceptance of client-provided CV URLs/paths during apply.
- Require a current uploaded CV before creating a new application.
- Persist server-owned snapshot metadata/reference atomically with the application record.
- Add a protected, non-public view/download endpoint that resolves the snapshot only after authorization.
- Surface a clear upload-CV call to action before apply when the student has no active CV.
- Render a safe file action for an authorized employer's application; do not expose raw paths.
- Ensure replacing/deleting an active CV cannot break a prior application snapshot.

Do NOT change:

- One-application-per-job rule, application statuses, cover letter, preferred shift, employer notes, or company ownership rules.
- Public student profile data or employer access to CVs outside submitted applications.

Acceptance criteria:

- `POST /jobs/{id}/applications` ignores/rejects `cv_url_snapshot`, `resume`, file ID, path, and `user_id` supplied by the client.
- Applying without an active uploaded CV returns `422` and creates neither an application nor notification.
- A successful application stores the exact active CV reference selected by the server within the same database transaction as application creation.
- Student A cannot retrieve Student B's profile CV or snapshot.
- Employer A cannot retrieve a CV from Employer B's application, including by changing an application ID.
- The authorized employer can retrieve only a CV associated with an application for one of its jobs.
- Replacing or deleting the active profile CV does not break viewing/downloading snapshots for older applications.
- Regression tests cover the above authorization and snapshot cases in isolated DB/storage.

Estimated size: Large

### CV-P1-01 — CV-ready application and profile polish

Current problem:
The student needs clear confirmation of which CV will be used, and the employer needs a clear authorized file action without exposing private implementation details.

Desired result:
The profile and apply flows clearly communicate CV readiness and the employer can access a submitted CV only from the application context.

Affected pages/components:

- `app/Views/student/profile.php`
- Job-detail/apply view and JavaScript
- `app/Views/student/applications.php`
- `app/Views/company/applications.php`
- Existing shared CSS/components only where needed

Implementation scope:

- Show active CV name, size, uploaded/updated time, and replace/delete actions to its owner.
- Show an actionable empty state for no CV and disable/block apply with an explanation before the request.
- In the apply confirmation, state that the current CV will be attached.
- In company applications, show an accessible "Xem CV" / "Tải CV" action only when the backend indicates a protected snapshot is available.
- Add loading, success, and failure states without changing UI design-system scope.

Do NOT change:

- Application business status or existing dashboard/navigation architecture.
- File formats, multi-CV support, parsing, or any public document links.

Acceptance criteria:

- Student can understand whether they are ready to apply without reading technical error messages.
- UI does not manufacture or expose any storage key/path/URL.
- Every user-visible CV request handles loading and server errors.
- Employer views contain no CV action for applicants without an accessible stored snapshot.
- Mobile layout remains usable.

Estimated size: Medium

### CV-P1-02 — Operations, retention, and deployment guidance

Current problem:
Private user files require backup, writable storage, and a defined retention policy before production deployment.

Desired result:
The team can deploy and operate CV storage safely without accidental data loss or exposure.

Affected pages/components:

- `docs/` only
- Deployment configuration examples if they already exist

Implementation scope:

- Document production storage path/permissions outside webroot in `docs/CV_OPERATIONS.md`.
- Document backup/restore for DB and private CV files as one recoverable unit.
- Define a retention decision for withdrawn applications and account deletion; do not implement automated deletion in this task.
- Document how production malware scanning will be added later if upload volume grows.
- Comprehensive guide created: [`docs/CV_OPERATIONS.md`](CV_OPERATIONS.md).

Do NOT change:

- Runtime behavior, database records, or existing deployments.

Acceptance criteria:

- Operations guide states where CV files live, who can read them, how they are backed up, and what must be restored together.
- Retention is explicitly marked as a product/legal policy decision if not yet approved.

Estimated size: Small

## Required test matrix

| Case | Expected result |
| --- | --- |
| Guest upload/delete/download | 401/403; no file or DB write |
| Company/admin upload/delete profile CV | 403; no file or DB write |
| Student uploads a valid PDF | private file and safe metadata persist |
| Spoofed PDF extension/MIME | 422; no file/reference persists |
| File over 5 MB | 422; no file/reference persists |
| Student updates another user's CV reference | 403/404; target unchanged |
| Apply with no uploaded CV | 422; no application/notification |
| Apply with request-supplied external CV URL | ignored/rejected; server-owned active CV is used only |
| Authorized company reads its applicant CV | success through protected endpoint |
| Other company/student reads same application CV | 403/404 |
| Replace/delete active CV after apply | old application snapshot remains available |
| Test teardown | isolated test DB and test storage are cleaned in `finally`; no development/shared storage is touched |

## Definition of done

CV-P0-01 and CV-P0-02 must be implemented and reviewed before public deployment. CV-P1-01 should follow before inviting students to the pilot. CV-P1-02 must be completed before production launch with real CV data.
