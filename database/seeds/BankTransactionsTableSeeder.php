<?php

use Illuminate\Database\Seeder;
use App\Model\BankTransaction;
class BankTransactionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

         BankTransaction::firstOrCreate(['transaction_name' => 'Basic']);
         BankTransaction::firstOrCreate(['transaction_name' => 'Bonus Coin']);
         BankTransaction::firstOrCreate(['transaction_name' => 'Gift Coin']);
         BankTransaction::firstOrCreate(['transaction_name' => 'Referral']);
         BankTransaction::firstOrCreate(['transaction_name' => 'Task Completed Points']);
         BankTransaction::firstOrCreate(['transaction_name' => 'Social Connects']);
         BankTransaction::firstOrCreate(['transaction_name' => 'Option Trade']);
    }
}
