<?php

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as SlimResponse;
use Fhp\FinTs;
use Fhp\Options\FinTsOptions;
use Fhp\Options\Credentials;
use Fhp\Action\GetSEPAAccounts;
use Fhp\Action\GetBalance;
use Fhp\Action\GetStatementOfAccount;
use Fhp\Model\NoPsd2TanMode;

class AccountController
{
    public function getBalance(Request $request, Response $response): Response
    {
        $slimResponse = new SlimResponse();
        
        try {
            // Get FinTS configuration from environment
            $bankUrl = $_ENV['FINTS_BANK_URL'] ?? '';
            $bankCode = $_ENV['FINTS_BANK_CODE'] ?? '';
            $username = $_ENV['FINTS_USERNAME'] ?? '';
            $pin = $_ENV['FINTS_PIN'] ?? '';
            
            if (empty($bankUrl) || empty($bankCode) || empty($username) || empty($pin)) {
                $slimResponse->getBody()->write(json_encode([
                    'error' => 'FinTS configuration incomplete'
                ]));
                
                return $slimResponse
                    ->withStatus(500)
                    ->withHeader('Content-Type', 'application/json');
            }
            
            // Create FinTS options
            $options = new FinTsOptions();
            $options->url = $bankUrl;
            $options->bankCode = $bankCode;
            $options->productName = 'HBCI-REST-Client';
            $options->productVersion = '1.0.0';
            
            // Create credentials
            $credentials = Credentials::create($username, $pin);
            
            // Initialize FinTS connection
            $fints = FinTs::new($options, $credentials);
            
            // Use NoPsd2TanMode directly since the bank doesn't support anonymous dialogs
            $selectedTanMode = new NoPsd2TanMode();
            $selectedTanMedium = null;
            
            // Select the TAN mode
            $fints->selectTanMode($selectedTanMode, $selectedTanMedium);
            
            // Login first - this establishes the dialog
            $login = $fints->login();
            if ($login->needsTan()) {
                $slimResponse->getBody()->write(json_encode([
                    'error' => 'TAN required for login'
                ]));
                
                return $slimResponse
                    ->withStatus(401)
                    ->withHeader('Content-Type', 'application/json');
            }
            
            // Get SEPA accounts
            $getSepaAccounts = GetSEPAAccounts::create();
            $fints->execute($getSepaAccounts);
            
            if ($getSepaAccounts->needsTan()) {
                $slimResponse->getBody()->write(json_encode([
                    'error' => 'TAN required for account access'
                ]));
                
                return $slimResponse
                    ->withStatus(401)
                    ->withHeader('Content-Type', 'application/json');
            }
            
            $accounts = $getSepaAccounts->getAccounts();
            if (empty($accounts)) {
                $slimResponse->getBody()->write(json_encode([
                    'error' => 'No accounts found'
                ]));
                
                return $slimResponse
                    ->withStatus(404)
                    ->withHeader('Content-Type', 'application/json');
            }
            
            // Get balance for the first account
            $firstAccount = $accounts[0];
            $getBalance = GetBalance::create($firstAccount, true);
            $fints->execute($getBalance);
            
            if ($getBalance->needsTan()) {
                $slimResponse->getBody()->write(json_encode([
                    'error' => 'TAN required for balance inquiry'
                ]));
                
                return $slimResponse
                    ->withStatus(401)
                    ->withHeader('Content-Type', 'application/json');
            }
            
            $balances = $getBalance->getBalances();
            if (empty($balances)) {
                $slimResponse->getBody()->write(json_encode([
                    'error' => 'No balance information available'
                ]));
                
                return $slimResponse
                    ->withStatus(404)
                    ->withHeader('Content-Type', 'application/json');
            }
            
            // Get the first balance
            $balance = $balances[0];
            $accountInfo = $balance->getAccountInfo();
            $gebuchterSaldo = $balance->getGebuchterSaldo();
            
            // Get additional account information from the SEPAAccount
            $sepaAccount = $firstAccount;
            
            $slimResponse->getBody()->write(json_encode([
                'success' => true,
                'account' => [
                    'number' => $accountInfo->getAccountNumber(),
                    'iban' => $sepaAccount->getIban(),
                    'bic' => $sepaAccount->getBic(),
                    'blz' => $sepaAccount->getBlz(),
                    'subAccount' => $sepaAccount->getSubAccount()
                ],
                'balance' => [
                    'amount' => $gebuchterSaldo->getAmount(),
                    'currency' => $gebuchterSaldo->getCurrency(),
                    'timestamp' => $gebuchterSaldo->getTimestamp()->format('c')
                ],
                'timestamp' => date('c')
            ]));
            
            return $slimResponse
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json');
                
        } catch (\Exception $e) {
            $slimResponse->getBody()->write(json_encode([
                'error' => 'Failed to retrieve balance: ' . $e->getMessage()
            ]));
            
            return $slimResponse
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    public function getTransactions(Request $request, Response $response): Response
    {
        $slimResponse = new SlimResponse();
        
        try {
            // Get query parameters for date range
            $fromDate = $request->getQueryParams()['from'] ?? '30 days ago';
            $toDate = $request->getQueryParams()['to'] ?? 'today';
            
            // Parse dates
            $from = new \DateTime($fromDate);
            $to = new \DateTime($toDate);
            
            // Get FinTS configuration from environment
            $bankUrl = $_ENV['FINTS_BANK_URL'] ?? '';
            $bankCode = $_ENV['FINTS_BANK_CODE'] ?? '';
            $username = $_ENV['FINTS_USERNAME'] ?? '';
            $pin = $_ENV['FINTS_PIN'] ?? '';
            
            if (empty($bankUrl) || empty($bankCode) || empty($username) || empty($pin)) {
                $slimResponse->getBody()->write(json_encode([
                    'error' => 'FinTS configuration incomplete'
                ]));
                
                return $slimResponse
                    ->withStatus(500)
                    ->withHeader('Content-Type', 'application/json');
            }
            
            // Create FinTS options
            $options = new FinTsOptions();
            $options->url = $bankUrl;
            $options->bankCode = $bankCode;
            $options->productName = 'HBCI-REST-Client';
            $options->productVersion = '1.0.0';
            
            // Create credentials
            $credentials = Credentials::create($username, $pin);
            
            // Initialize FinTS connection
            $fints = FinTs::new($options, $credentials);
            
            // Use NoPsd2TanMode directly since the bank doesn't support anonymous dialogs
            $selectedTanMode = new NoPsd2TanMode();
            $selectedTanMedium = null;
            
            // Select the TAN mode
            $fints->selectTanMode($selectedTanMode, $selectedTanMedium);
            
            // Login first - this establishes the dialog
            $login = $fints->login();
            if ($login->needsTan()) {
                $slimResponse->getBody()->write(json_encode([
                    'error' => 'TAN required for login'
                ]));
                
                return $slimResponse
                    ->withStatus(401)
                    ->withHeader('Content-Type', 'application/json');
            }
            
            // Get SEPA accounts
            $getSepaAccounts = GetSEPAAccounts::create();
            $fints->execute($getSepaAccounts);
            
            if ($getSepaAccounts->needsTan()) {
                $slimResponse->getBody()->write(json_encode([
                    'error' => 'TAN required for account access'
                ]));
                
                return $slimResponse
                    ->withStatus(401)
                    ->withHeader('Content-Type', 'application/json');
            }
            
            $accounts = $getSepaAccounts->getAccounts();
            if (empty($accounts)) {
                $slimResponse->getBody()->write(json_encode([
                    'error' => 'No accounts found'
                ]));
                
                return $slimResponse
                    ->withStatus(404)
                    ->withHeader('Content-Type', 'application/json');
            }
            
            // Get transactions for the first account
            $firstAccount = $accounts[0];
            $getStatement = GetStatementOfAccount::create($firstAccount, $from, $to, false, true);
            $fints->execute($getStatement);
            
            if ($getStatement->needsTan()) {
                $slimResponse->getBody()->write(json_encode([
                    'error' => 'TAN required for transaction inquiry'
                ]));
                
                return $slimResponse
                    ->withStatus(401)
                    ->withHeader('Content-Type', 'application/json');
            }
            
            $soa = $getStatement->getStatement();
            $statements = $soa->getStatements();
            
            if (empty($statements)) {
                $slimResponse->getBody()->write(json_encode([
                    'error' => 'No transaction statements available'
                ]));
                
                return $slimResponse
                    ->withStatus(404)
                    ->withHeader('Content-Type', 'application/json');
            }
            
            // Process all transactions
            $allTransactions = [];
            foreach ($statements as $statement) {
                $statementData = [
                    'date' => $statement->getDate()->format('Y-m-d'),
                    'startBalance' => $statement->getStartBalance(),
                    'creditDebit' => $statement->getCreditDebit(),
                    'transactions' => []
                ];
                
                foreach ($statement->getTransactions() as $transaction) {
                    $transactionData = [
                        'booked' => $transaction->getBooked(),
                        'amount' => $transaction->getAmount(),
                        'creditDebit' => $transaction->getCreditDebit(),
                        'bookingText' => $transaction->getBookingText(),
                        'name' => $transaction->getName(),
                        'description' => $transaction->getMainDescription(),
                        'endToEndId' => $transaction->getEndToEndID(),
                        'valutaDate' => $transaction->getValutaDate() ? $transaction->getValutaDate()->format('Y-m-d') : null,
                        'bookingDate' => $transaction->getBookingDate() ? $transaction->getBookingDate()->format('Y-m-d') : null
                    ];
                    
                    $statementData['transactions'][] = $transactionData;
                }
                
                $allTransactions[] = $statementData;
            }
            
            // Get account information
            $sepaAccount = $firstAccount;
            
            $slimResponse->getBody()->write(json_encode([
                'success' => true,
                'account' => [
                    'number' => $sepaAccount->getAccountNumber(),
                    'iban' => $sepaAccount->getIban(),
                    'bic' => $sepaAccount->getBic(),
                    'blz' => $sepaAccount->getBlz(),
                    'subAccount' => $sepaAccount->getSubAccount()
                ],
                'dateRange' => [
                    'from' => $from->format('Y-m-d'),
                    'to' => $to->format('Y-m-d')
                ],
                'statements' => $allTransactions,
                'totalStatements' => count($statements),
                'timestamp' => date('c')
            ]));
            
            return $slimResponse
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json');
                
        } catch (\Exception $e) {
            $slimResponse->getBody()->write(json_encode([
                'error' => 'Failed to retrieve transactions: ' . $e->getMessage()
            ]));
            
            return $slimResponse
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }
}
