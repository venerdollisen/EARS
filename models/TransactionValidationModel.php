<?php
/**
 * Transaction Validation Model
 * Handles validation of transaction entries based on accounting principles
 */

class TransactionValidationModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Validate transaction distributions based on account types
     * @param array $distributions Array of transaction distributions
     * @return array Validation result with success status and errors
     */
    public function validateTransactionDistributions($distributions) {
        $errors = [];
        $warnings = [];
        
        foreach ($distributions as $index => $distribution) {
            $accountId = $distribution['account_id'];
            $paymentType = strtolower($distribution['payment_type']); // debit or credit
            $amount = $distribution['amount'];
            
            // Get account details
            $accountInfo = $this->getAccountInfo($accountId);
            if (!$accountInfo) {
                $errors[] = "Distribution #" . ($index + 1) . ": Account ID $accountId not found";
                continue;
            }
            
            // Validate payment type based on account type
            $validationResult = $this->validatePaymentTypeForAccount(
                $accountInfo['account_type'], 
                $paymentType, 
                $amount
            );
            
            if (!$validationResult['valid']) {
                $errors[] = "Distribution #" . ($index + 1) . ": " . $validationResult['message'];
            }
            
            if (isset($validationResult['warning'])) {
                $warnings[] = "Distribution #" . ($index + 1) . ": " . $validationResult['warning'];
            }
        }
        
        // Check if debits equal credits
        $balanceResult = $this->validateDebitCreditBalance($distributions);
        if (!$balanceResult['balanced']) {
            $errors[] = $balanceResult['message'];
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'balanced' => $balanceResult['balanced']
        ];
    }
    
    /**
     * Get account information including account type
     * @param int $accountId
     * @return array|false Account info or false if not found
     */
    private function getAccountInfo($accountId) {
        $sql = "
            SELECT 
                coa.id,
                coa.account_code,
                coa.account_name,
                cat.type_name as account_type
            FROM chart_of_accounts coa
            JOIN coa_account_types cat ON coa.account_type_id = cat.id
            WHERE coa.id = :account_id
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['account_id' => $accountId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Derive normal balance based on account type
            $result['normal_balance'] = $this->getNormalBalanceForAccountType($result['account_type']);
        }
        
        return $result;
    }
    
    /**
     * Get normal balance for account type
     * @param string $accountType
     * @return string debit or credit
     */
    private function getNormalBalanceForAccountType($accountType) {
        $accountType = strtolower($accountType);
        
        $normalBalances = [
            'asset' => 'debit',
            'liability' => 'credit', 
            'equity' => 'credit',
            'revenue' => 'credit',
            'expense' => 'debit'
        ];
        
        return $normalBalances[$accountType] ?? 'debit'; // Default to debit if unknown
    }
    
    /**
     * Validate payment type (debit/credit) for specific account type
     * @param string $accountType Asset, Liability, Equity, Revenue, Expense
     * @param string $paymentType debit or credit
     * @param float $amount
     * @return array Validation result
     */
    private function validatePaymentTypeForAccount($accountType, $paymentType, $amount) {
        $accountType = strtolower($accountType);
        $paymentType = strtolower($paymentType);
        
        // Define normal balance for each account type
        $normalBalances = [
            'asset' => 'debit',
            'liability' => 'credit', 
            'equity' => 'credit',
            'revenue' => 'credit',
            'expense' => 'debit'
        ];
        
        if (!isset($normalBalances[$accountType])) {
            return [
                'valid' => false,
                'message' => "Unknown account type: $accountType"
            ];
        }
        
        $normalBalance = $normalBalances[$accountType];
        
        // Check if this is an increase or decrease transaction
        $isIncrease = ($paymentType === $normalBalance);
        $isDecrease = ($paymentType !== $normalBalance);
        
        // Validate based on account type and transaction type
        switch ($accountType) {
            case 'asset':
                if ($isDecrease && $amount > 0) {
                    // Decreasing an asset (credit) - this is normal for payments
                    return ['valid' => true];
                } elseif ($isIncrease && $amount > 0) {
                    // Increasing an asset (debit) - this is normal for receipts
                    return ['valid' => true];
                } else {
                    return [
                        'valid' => false,
                        'message' => "Asset account should be debited to increase or credited to decrease"
                    ];
                }
                
            case 'liability':
                if ($isIncrease && $amount > 0) {
                    // Increasing a liability (credit) - this is normal for purchases on credit
                    return ['valid' => true];
                } elseif ($isDecrease && $amount > 0) {
                    // Decreasing a liability (debit) - this is normal for payments
                    return ['valid' => true];
                } else {
                    return [
                        'valid' => false,
                        'message' => "Liability account should be credited to increase or debited to decrease"
                    ];
                }
                
            case 'equity':
                if ($isIncrease && $amount > 0) {
                    // Increasing equity (credit) - this is normal for investments
                    return ['valid' => true];
                } elseif ($isDecrease && $amount > 0) {
                    // Decreasing equity (debit) - this is normal for withdrawals
                    return ['valid' => true];
                } else {
                    return [
                        'valid' => false,
                        'message' => "Equity account should be credited to increase or debited to decrease"
                    ];
                }
                
            case 'revenue':
                if ($isIncrease && $amount > 0) {
                    // Increasing revenue (credit) - this is normal for sales
                    return ['valid' => true];
                } elseif ($isDecrease && $amount > 0) {
                    // Decreasing revenue (debit) - this might be a refund or adjustment
                    return [
                        'valid' => true,
                        'warning' => "Revenue decrease detected - ensure this is intentional (refund/adjustment)"
                    ];
                } else {
                    return [
                        'valid' => false,
                        'message' => "Revenue account should be credited to increase or debited to decrease"
                    ];
                }
                
            case 'expense':
                if ($isIncrease && $amount > 0) {
                    // Increasing expense (debit) - this is normal for costs
                    return ['valid' => true];
                } elseif ($isDecrease && $amount > 0) {
                    // Decreasing expense (credit) - this might be a refund or adjustment
                    return [
                        'valid' => true,
                        'warning' => "Expense decrease detected - ensure this is intentional (refund/adjustment)"
                    ];
                } else {
                    return [
                        'valid' => false,
                        'message' => "Expense account should be debited to increase or credited to decrease"
                    ];
                }
                
            default:
                return [
                    'valid' => false,
                    'message' => "Unsupported account type: $accountType"
                ];
        }
    }
    
    /**
     * Validate that total debits equal total credits
     * @param array $distributions
     * @return array Balance validation result
     */
    private function validateDebitCreditBalance($distributions) {
        $totalDebits = 0;
        $totalCredits = 0;
        
        foreach ($distributions as $distribution) {
            $amount = floatval($distribution['amount']);
            $paymentType = strtolower($distribution['payment_type']);
            
            if ($paymentType === 'debit') {
                $totalDebits += $amount;
            } elseif ($paymentType === 'credit') {
                $totalCredits += $amount;
            }
        }
        
        $difference = abs($totalDebits - $totalCredits);
        $tolerance = 0.01; // Allow for small rounding differences
        
        if ($difference > $tolerance) {
            return [
                'balanced' => false,
                'message' => "Debits (₱" . number_format($totalDebits, 2) . ") do not equal Credits (₱" . number_format($totalCredits, 2) . "). Difference: ₱" . number_format($difference, 2)
            ];
        }
        
        return [
            'balanced' => true,
            'message' => "Transaction is balanced: Debits = ₱" . number_format($totalDebits, 2) . ", Credits = ₱" . number_format($totalCredits, 2)
        ];
    }
    
    /**
     * Get validation rules for display/help
     * @return array Validation rules
     */
    public function getValidationRules() {
        return [
            'asset' => [
                'normal_balance' => 'debit',
                'increase' => 'debit',
                'decrease' => 'credit',
                'examples' => [
                    'Cash receipt: Debit Cash, Credit Revenue',
                    'Payment: Credit Cash, Debit Expense'
                ]
            ],
            'liability' => [
                'normal_balance' => 'credit',
                'increase' => 'credit',
                'decrease' => 'debit',
                'examples' => [
                    'Purchase on credit: Credit Accounts Payable, Debit Expense',
                    'Payment: Debit Accounts Payable, Credit Cash'
                ]
            ],
            'equity' => [
                'normal_balance' => 'credit',
                'increase' => 'credit',
                'decrease' => 'debit',
                'examples' => [
                    'Investment: Credit Owner\'s Equity, Debit Cash',
                    'Withdrawal: Debit Owner\'s Equity, Credit Cash'
                ]
            ],
            'revenue' => [
                'normal_balance' => 'credit',
                'increase' => 'credit',
                'decrease' => 'debit',
                'examples' => [
                    'Sale: Credit Sales Revenue, Debit Cash',
                    'Refund: Debit Sales Revenue, Credit Cash'
                ]
            ],
            'expense' => [
                'normal_balance' => 'debit',
                'increase' => 'debit',
                'decrease' => 'credit',
                'examples' => [
                    'Expense: Debit Expense Account, Credit Cash',
                    'Refund: Credit Expense Account, Debit Cash'
                ]
            ]
        ];
    }
}
?>
