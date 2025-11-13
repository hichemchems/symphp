# TODO: Commission Verification and Dashboard Simplification

## 1. Verify Commission Calculations
- [ ] Review GenerateWeeklyCommissionsCommand.php to ensure correct HT calculation (revenues are already HT)
- [ ] Verify commission percentage application: totalCommission = revenueHt * (commissionPercentage / 100)
- [ ] Check if TVA is correctly handled (revenues stored as HT, TTC calculated as HT * 1.20)
- [ ] Test calculation logic for multiple employees

## 2. Simplify Employee Dashboard for Mobile
- [ ] Modify templates/employee_dashboard/index.html.twig
  - [ ] Replace monthly cards with weekly cards (turnover and commission)
  - [ ] Add pending commissions card (unvalidated weekly commissions)
  - [ ] Add validated and paid commissions card
  - [ ] Update mobile responsiveness
- [ ] Update EmployeeDashboardController.php if needed for new data fetching

## 3. Add Burger Menu for Mobile
- [ ] Add burger menu button in top right
- [ ] Menu contains:
  - [ ] History button (all executed packages)
  - [ ] Logout button
- [ ] Close menu on outside click
- [ ] Mobile-friendly styling

## 4. Update Commission History Logic
- [ ] Ensure commission history shows validated commissions
- [ ] Update regeneration after admin validation
- [ ] Move previous commissions to history when new ones are validated

## 5. Testing and Validation
- [ ] Test commission calculations with sample data
- [ ] Test mobile dashboard layout
- [ ] Test burger menu functionality
- [ ] Verify commission flow: pending -> validated -> paid -> history
