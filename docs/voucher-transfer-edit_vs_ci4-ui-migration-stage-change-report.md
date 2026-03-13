# Branch Change Report
## Comparison
- **Base branch:** `ci4-ui-migration-stage`
- **Target branch:** `voucher-transfer-edit`

- **Unique commit on target branch:** `7f1c15d0` — _Prevent editing submitted vouchers and funds transfers_

## Change Summary
- **Files changed:** 3
- **Insertions/Deletions:** 31 insertions, 42 deletions
- **Changed files:**
  1. `app/Controllers/Web/WebController.php`
  2. `app/Libraries/Grants/VoucherLibrary.php`
  3. `public/script/listBuilder/ListRender.js`

## Functional Changes
### 1) List API payload now carries row-level edit visibility
**File:** `app/Controllers/Web/WebController.php`

- `showListEditAction(...)` is computed once per row and stored in `rowInfo.showEditAction`.
- This value is now included in each row’s metadata sent to the frontend.


**Impact:**
- Frontend receives explicit row-level edit permission/eligibility state from backend.

---

### 2) Voucher edit eligibility logic kept explicit and centralized
**File:** `app/Libraries/Grants/VoucherLibrary.php`

- `showListEditAction(...)` still enforces that edit is **not allowed** when:
  - voucher is at max approval status,
  - voucher is reversed,
  - voucher is not at initial status.
- Method was refactored into a direct return expression with same business logic.

**Impact:**
- Maintains strict edit constraints for submitted/reversed/non-initial vouchers while improving readability.

---

### 3) Frontend action column now respects backend `showEditAction`
**File:** `public/script/listBuilder/ListRender.js`

- Action-cell renderer signature now takes `showEditAction`.
- Edit button rendering changed from:
  - `canUpdate` only
  to
  - `canUpdate && showEditAction`
- Row rendering now passes `rowInfo.showEditAction` into action rendering.

**Impact:**
- Even if user has update permission, Edit button is hidden when backend says record is not editable.
- Aligns UI behavior with approval/reversal workflow constraints.

---

## Test Plan
## Scope
Validate that list-page Edit button visibility correctly follows backend workflow rules for vouchers (and other features using the same list renderer contract).

## Preconditions
1. Environment deployed from branch `voucher-transfer-edit`.
2. At least one user with update permission for Voucher.
3. Test data with vouchers in these states:
   - Initial status, not reversed
   - Max approval/submitted status
   - Reversed voucher
   - Non-initial intermediate status

## Test Cases
### A. Voucher list behavior
1. **Initial + not reversed + user can update**
   - Open voucher list.
   - Verify Edit action is visible.
   - Expected: Edit link/button is shown.

2. **Max approval/submitted voucher**
   - Open voucher list with submitted voucher row.
   - Expected: Edit link/button is hidden.

3. **Reversed voucher**
   - Open voucher list with reversed voucher row.
   - Expected: Edit link/button is hidden.

4. **Non-initial status voucher**
   - Open voucher list with voucher in non-initial status.
   - Expected: Edit link/button is hidden.

5. **User without update permission**
   - Login as user lacking update permission.
   - Expected: Edit link/button hidden for all rows regardless of `showEditAction`.

### B. API contract checks
6. **Row metadata includes edit flag**
   - Inspect show-list response payload.
   - Expected: `rowInfo.showEditAction` present for each row as boolean.

7. **Backend/frontend consistency**
   - For a known non-editable row (`showEditAction=false`), confirm no Edit button in UI.
   - Expected: UI strictly follows payload value.

### C. Regression checks for action column
8. **View/Delete actions unaffected**
   - Verify non-edit actions still behave by permission/status as before.
   - Expected: only Edit visibility changed; View/Delete behavior unchanged.

9. **Attachments/comments badges unaffected**
   - Verify row-level attachments/comments counts still render.
   - Expected: no regressions in row metadata consumption.

### D. Cross-feature smoke (shared renderer)
10. **Feature using same `ListRender` action cell (e.g., funds transfer)**
    - Open feature list and verify Edit visibility follows backend-calculated row rules.
    - Expected: no unconditional Edit rendering from `canUpdate` alone.

## Suggested Execution Order
1. API contract check (Case 6)
2. Voucher core scenarios (Cases 1–5)
3. Regression checks (Cases 8–9)
4. Shared renderer smoke (Case 10)

## Pass/Fail Criteria
- **Pass:** Edit button is visible only when both conditions are true: user can update **and** `showEditAction=true` for the row.
- **Fail:** Any submitted/reversed/non-initial voucher still exposes Edit action, or editable initial vouchers lose Edit unexpectedly.
