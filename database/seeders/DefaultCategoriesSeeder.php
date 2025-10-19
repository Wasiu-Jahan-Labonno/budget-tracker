<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class DefaultCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        // For all users (or adapt to a specific user during registration)
        foreach (User::cursor() as $user) {
            $income = ['Salary','Bonus','Gift'];
            $expense = ['Rent','Food','Transport','Utilities','Healthcare','Education','Shopping','Entertainment','Other'];

            foreach ($income as $n) {
                Category::firstOrCreate(['user_id'=>$user->id,'name'=>$n,'type'=>'income']);
            }
            foreach ($expense as $n) {
                Category::firstOrCreate(['user_id'=>$user->id,'name'=>$n,'type'=>'expense']);
            }
        }
    }
}
