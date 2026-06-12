<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MessageService
{
    /**
     * The Africa's Talking API configuration
     */
    protected string $apiUrl;
    protected string $username;
    protected string $apiKey;
    protected string $from;

    /**
     * Create a new MessageService instance.
     */
    public function __construct()
    {
        $this->apiUrl = config('services.africastalking.api_url', 'https://api.africastalking.com/version1/messaging');
        $this->username = config('services.africastalking.username');
        $this->apiKey = config('services.africastalking.api_key');
        $this->from = config('services.africastalking.from', 'AFRICASTALKING');
    }

    /**
     * Send SMS message via Africa's Talking API
     *
     * @param string $phoneNumber
     * @param string $message
     * @return array
     * @throws Exception
     */
    public function sendMessage(string $phoneNumber, string $message): array
    {
        try {
            // Format phone number to international format
            $formattedPhone = $this->formatMobileInternational($phoneNumber);

            Log::info('Sending SMS', [
                'phone' => $formattedPhone,
                'message_length' => strlen($message)
            ]);

            // Send HTTP request to Africa's Talking API
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'apiKey' => $this->apiKey,
            ])->asForm()->post($this->apiUrl, [
                'username' => $this->username,
                'to' => $formattedPhone,
                'message' => $message,
                'from' => $this->from,
            ]);

            // Check if request was successful
            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('SMS sent successfully', [
                    'phone' => $formattedPhone,
                    'response' => $responseData
                ]);

                $recipientData = $responseData['SMSMessageData']['Recipients'][0] ?? null;
                $apiStatus = $recipientData['status'] ?? 'Sent';
                
                // Normalize status to lowercase
                $normalizedStatus = strtolower($apiStatus);
                if ($normalizedStatus === 'sent' || $normalizedStatus === 'delivered' || $normalizedStatus === 'queued') {
                    $normalizedStatus = 'sent'; // Treat as sent for balance deduction
                }

                return [
                    'success' => true,
                    'data' => $responseData,
                    'message' => 'Message sent successfully',
                    'message_id' => $recipientData['messageId'] ?? null,
                    'status' => $normalizedStatus,
                    'api_status' => $apiStatus,
                    'cost' => $recipientData['cost'] ?? null,
                ];
            }

            // Handle API errors
            Log::error('SMS sending failed', [
                'phone' => $formattedPhone,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => $response->body(),
                'message' => 'Failed to send message',
                'status_code' => $response->status(),
            ];

        } catch (Exception $e) {
            Log::error('SMS Exception', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'An error occurred while sending message'
            ];
        }
    }

    /**
     * Send SMS to multiple recipients
     *
     * @param array $phoneNumbers
     * @param string $message
     * @return array
     */
    public function sendBulkMessage(array $phoneNumbers, string $message): array
    {
        $results = [];
        
        foreach ($phoneNumbers as $phoneNumber) {
            $results[] = $this->sendMessage($phoneNumber, $message);
        }

        return $results;
    }

    /**
     * Normalize phone number by removing country code and formatting
     * Returns the 9-digit number without country code
     *
     * @param string $mobile
     * @return string
     */
    protected function normalizePhoneNumber(string $mobile): string
    {
        // Remove + and whitespace
        $number = ltrim(trim($mobile), '+');
        
        // Remove Uganda country code (256) if present
        if (str_starts_with($number, '256')) {
            $number = substr($number, 3);
        }
        
        // Remove single leading 0 if present (local format)
        if (str_starts_with($number, '0') && strlen($number) > 1) {
            $number = substr($number, 1);
        }
        
        return $number;
    }

    /**
     * Format mobile number to international format (+256XXXXXXXXX)
     *
     * @param string $mobile
     * @return string
     */
    public function formatMobileInternational(string $mobile): string
    {
        // Normalize the number first
        $normalized = $this->normalizePhoneNumber($mobile);
        
        // If we have a valid 9-digit number, format it
        if (preg_match('/^[0-9]{9}$/', $normalized)) {
            return '+256' . $normalized;
        }
        
        // If already in international format, return as is
        if (preg_match('/^\+256[0-9]{9}$/', $mobile)) {
            return $mobile;
        }

        // Return as is if format is not recognized
        return $mobile;
    }

    /**
     * Validate if phone number is in correct format (Uganda only)
     *
     * @param string $mobile
     * @return bool
     */
    public function isValidPhoneNumber(string $mobile): bool
    {
        try {
            // Normalize the number first
            $normalized = $this->normalizePhoneNumber($mobile);
            
            // Uganda mobile numbers should be 9 digits starting with 7 or 3
            // This covers all valid mobile prefixes: 070-079, 030-039
            if (!preg_match('/^[73][0-9]{8}$/', $normalized)) {
                return false;
            }
            
            // Format to international and verify final format
            $formatted = $this->formatMobileInternational($mobile);
            
            // Final check: should be +256 followed by 9 digits
            return preg_match('/^\+256[0-9]{9}$/', $formatted) === 1;
            
        } catch (\Exception $e) {
            Log::error('Phone number validation error', [
                'phone_number' => $mobile,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Check if a phone number is an international (non-Uganda) number.
     * Returns true if the number is from any African country except Uganda (+256).
     *
     * @param string $mobile
     * @return bool
     */
    public function isInternationalNumber(string $mobile): bool
    {
        try {
            // Remove whitespace
            $number = trim($mobile);
            
            // Must start with +
            if (!str_starts_with($number, '+')) {
                return false;
            }

            // Remove the + and check country code
            $number = substr($number, 1);
            
            // Check if it's Uganda (+256)
            if (str_starts_with($number, '256')) {
                return false; // Uganda is not international
            }
            
            // Check if it's an African country code (excluding 256)
            $africanCountryCodes = [
                '20', '27', '212', '213', '216', '218', '220', '221', '222', '223', 
                '224', '225', '226', '227', '228', '229', '230', '231', '232', '233', 
                '234', '235', '236', '237', '238', '239', '240', '241', '242', '243', 
                '244', '245', '248', '249', '250', '251', '252', '253', '254', '255', 
                '257', '258', '260', '261', '262', '263', '264', '265', '266', '267', 
                '268', '269', '290', '291'
            ];
            
            foreach ($africanCountryCodes as $code) {
                if (str_starts_with($number, $code)) {
                    return true; // It's an international African number
                }
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error('International number check error', [
                'phone_number' => $mobile,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Validate if phone number is a valid international African number
     * Supports all African country codes
     *
     * @param string $mobile
     * @return bool
     */
    public function isValidInternationalPhoneNumber(string $mobile): bool
    {
        try {
            // Remove whitespace and ensure it starts with +
            $number = trim($mobile);
            if (!str_starts_with($number, '+')) {
                return false;
            }

            // Remove the + and get the country code and number
            $number = substr($number, 1);
            
            // African country codes (2-3 digits)
            // Common African country codes: 20 (Egypt), 27 (South Africa), 212 (Morocco), 
            // 213 (Algeria), 216 (Tunisia), 218 (Libya), 220 (Gambia), 221 (Senegal),
            // 222 (Mauritania), 223 (Mali), 224 (Guinea), 225 (Ivory Coast), 226 (Burkina Faso),
            // 227 (Niger), 228 (Togo), 229 (Benin), 230 (Mauritius), 231 (Liberia),
            // 232 (Sierra Leone), 233 (Ghana), 234 (Nigeria), 235 (Chad), 236 (Central African Republic),
            // 237 (Cameroon), 238 (Cape Verde), 239 (São Tomé and Príncipe), 240 (Equatorial Guinea),
            // 241 (Gabon), 242 (Republic of the Congo), 243 (DRC), 244 (Angola), 245 (Guinea-Bissau),
            // 246 (British Indian Ocean Territory), 248 (Seychelles), 249 (Sudan), 250 (Rwanda),
            // 251 (Ethiopia), 252 (Somalia), 253 (Djibouti), 254 (Kenya), 255 (Tanzania),
            // 256 (Uganda), 257 (Burundi), 258 (Mozambique), 260 (Zambia), 261 (Madagascar),
            // 262 (Mayotte/Reunion), 263 (Zimbabwe), 264 (Namibia), 265 (Malawi), 266 (Lesotho),
            // 267 (Botswana), 268 (Eswatini), 269 (Comoros), 290 (Saint Helena), 291 (Eritrea),
            // 297 (Aruba - not African but included), 298 (Faroe Islands - not African)
            
            // Pattern: African country code (2-3 digits) followed by 7-12 digits
            // This is a general pattern - actual validation would need country-specific rules
            if (preg_match('/^(20|27|212|213|216|218|220|221|222|223|224|225|226|227|228|229|230|231|232|233|234|235|236|237|238|239|240|241|242|243|244|245|248|249|250|251|252|253|254|255|256|257|258|260|261|262|263|264|265|266|267|268|269|290|291)[0-9]{7,12}$/', $number)) {
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error('International phone number validation error', [
                'phone_number' => $mobile,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Get SMS balance (if Africa's Talking provides this endpoint)
     *
     * @return array
     */
    public function getBalance(): array
    {
        try {
            // Note: You may need to adjust this based on Africa's Talking API documentation
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'apiKey' => $this->apiKey,
            ])->get('https://api.africastalking.com/version1/user', [
                'username' => $this->username
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->body()
            ];

        } catch (Exception $e) {
            Log::error('Failed to get SMS balance', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
