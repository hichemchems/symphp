# TODO List for Statistics and Commissions Fix

## 1. Modify AdminDashboardController
- [x] Change getEmployeeStats to use monthly period instead of daily
- [x] Update employee stats calculation to accumulate monthly

## 2. Create ResetMonthlyStatsCommand
- [x] Create src/Command/ResetMonthlyStatsCommand.php
- [x] Archive monthly global stats (revenue, commissions, profit)
- [x] Reset current month stats to 0

## 3. Add Employee Details Route
- [x] Add new route in AdminDashboardController for employee details
- [x] Create method to show detailed stats (daily/weekly/monthly revenue/clients/commissions)

## 4. Update Admin Dashboard Template
- [x] Make employee cards clickable
- [x] Link to new employee details route

## 5. Update Employee Dashboard Controller
- [x] Add method to get commission history (validated commissions)
- [x] Modify index method to pass commission history data

## 6. Update Employee Dashboard Template
- [x] Add commission history modal
- [x] Display validated commissions with media queries

## 7. Testing
- [ ] Test monthly accumulation
- [ ] Test monthly reset
- [ ] Test employee card clicks
- [ ] Test commission history modal

## 8. Git Push and Deployment
- [ ] Commit all changes
- [ ] Push to repository
- [ ] Provide pull commands for cPanel
