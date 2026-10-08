<?php

namespace Database\Seeders;

use App\Models\BookedInfo;
use App\Models\Bookingtype;
use App\Models\Customerinfo;
use App\Models\Hall;
use App\Models\HallFacility;
use App\Models\HallSeatPlan;
use App\Models\HallType;
use App\Models\HkChecklistItem;
use App\Models\HkLaundryCost;
use App\Models\HkLaundryProduct;
use App\Models\HrAttendance;
use App\Models\HrAward;
use App\Models\HrCandidate;
use App\Models\HrDepartment;
use App\Models\HrEmployee;
use App\Models\HrEmployeeEducation;
use App\Models\HrEmployeeExperience;
use App\Models\HrHoliday;
use App\Models\HrLeaveType;
use App\Models\HrPosition;
use App\Models\HrRoster;
use App\Models\HrShift;
use App\Models\HrSalaryComponent;
use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\LedgerAccount;
use App\Models\PaymentMethod;
use App\Models\Promocode;
use App\Models\Roomdetails;
use App\Models\Roomfacilitydetails;
use App\Models\Roomfacilitytype;
use App\Models\RoomfailityRefAccomodation;
use App\Models\Setting;
use App\Models\Starclass;
use App\Models\Supplier;
use App\Models\TblComplementary;
use App\Models\TblFloor;
use App\Models\TblOtherguest;
use App\Models\TblRoomnofloorassign;
use App\Models\TblRoomOffer;
use App\Models\TblTaxmgt;
use App\Models\TblWakeupCall;
use App\Models\TrFlight;
use App\Models\TrVehicle;
use App\Models\TrVehicleBooking;
use App\Models\Unit;
use App\Models\User;
use App\Services\HallService;
use App\Services\Hr\LeaveService;
use App\Services\Hr\LoanService;
use App\Services\Hr\PayrollService;
use App\Services\HousekeepingService;
use App\Services\InventoryService;
use App\Services\LaundryService;
use App\Services\LedgerService;
use App\Services\PaymentService;
use App\Services\PurchaseService;
use App\Services\ReservationService;
use App\Support\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Sample data for every module, written through the same services the screens use so the ledger,
 * stock and balances are consistent. Run with `php artisan hotel:demo-data` on an empty installation.
 */
class DemoDataSeeder extends Seeder
{
    private ?int $uid = null;

    public function run(): void
    {
        $this->call([BaseDataSeeder::class, ChartOfAccountsSeeder::class, RolesAndPermissionsSeeder::class, PagesSeeder::class]);

        $admin = User::where('usertype', 1)->first();
        if (! $admin) {
            $admin = User::create(['firstname' => 'System', 'lastname' => 'Administrator', 'email' => 'admin@example.com', 'password' => Hash::make('password'), 'status' => 1, 'usertype' => 1, 'is_admin' => 1]);
            $admin->assignRole('Super Admin');
        }
        $this->uid = $admin->id;

        $this->hotel();
        $rooms = $this->rooms();
        $customers = $this->customers();
        $this->openingBalance();
        $this->reservations($rooms, $customers);
        $this->purchasing();
        $this->hr();
        $this->housekeeping();
        $this->transport($customers);
        $this->halls();
    }

    private function hotel(): void
    {
        Setting::find(Settings::ROW_ID)?->update([
            'title' => 'Grand Palace Hotel', 'storename' => 'Grand Palace Hotel', 'address' => '12 Lake Road, Gulshan, Dhaka', 'email' => 'info@grandpalace.test', 'phone' => '+8801700000000',
            'servicecharge' => 10, 'footer_text' => '© Grand Palace Hotel',
        ]);
        Settings::flush();
        TblTaxmgt::firstOrCreate(['taxname' => 'VAT'], ['rate' => 5, 'isactive' => 1]);
        foreach (['Booking.com', 'Agoda', 'Expedia', 'Corporate', 'Travel agent'] as $t) {
            Bookingtype::firstOrCreate(['booktypetitle' => $t]);
        }
    }

    /** @return array<string,Roomdetails> */
    private function rooms(): array
    {
        $floors = [];
        foreach (['Ground floor', '1st floor', '2nd floor', '3rd floor'] as $name) {
            $floors[$name] = TblFloor::firstOrCreate(['floorname' => $name], ['status' => 1]);
        }
        $bed = fn (string $t) => \App\Models\Bedstype::where('bedstypetitle', $t)->value('Bedstypeid');
        $size = \App\Models\Roomsizemesurement::first();
        $star = Starclass::where('starclassname', '4 Star')->first();

        $types = [
            'Standard Single' => ['rate' => 60, 'cap' => 1, 'bed' => 'Single', 'size' => 180, 'rooms' => [101, 102, 103, 104], 'floor' => 'Ground floor', 'desc' => 'Compact room with a single bed and a city view.'],
            'Deluxe Double' => ['rate' => 100, 'cap' => 2, 'bed' => 'Queen', 'size' => 300, 'rooms' => [201, 202, 203, 204, 205], 'floor' => '1st floor', 'desc' => 'Spacious double room with a balcony.'],
            'Family Suite' => ['rate' => 180, 'cap' => 4, 'bed' => 'King', 'size' => 520, 'rooms' => [301, 302, 303], 'floor' => '2nd floor', 'desc' => 'Two bedrooms and a living area for the whole family.'],
            'Presidential Suite' => ['rate' => 400, 'cap' => 4, 'bed' => 'King', 'size' => 900, 'rooms' => [401, 402], 'floor' => '3rd floor', 'desc' => 'Our best suite: panoramic view, lounge and private dining.'],
        ];
        $out = [];
        foreach ($types as $name => $t) {
            $room = Roomdetails::firstOrCreate(['roomtype' => $name], [
                'roomsize' => $t['size'], 'roomsizemesurement' => (string) $size->mesurementid, 'roomactive' => 1, 'bedsno' => 1, 'bedstype' => $bed($t['bed']),
                'number_of_star' => $star?->starcalssid, 'roomdescription' => $t['desc'], 'capacity' => $t['cap'], 'exbedcapability' => 1, 'child_limit' => 2,
                'rate' => $t['rate'], 'bedcharge' => 20, 'personcharge' => 15,
            ]);
            foreach ($t['rooms'] as $no) {
                TblRoomnofloorassign::firstOrCreate(['roomno' => $no], ['roomid' => $room->roomid, 'floorid' => $floors[$t['floor']]->floorid, 'status' => 1]);
            }
            $out[$name] = $room;
        }

        $type = Roomfacilitytype::firstOrCreate(['facilitytypetitle' => 'Room amenities']);
        foreach (['Free WiFi', 'Air conditioning', 'Smart TV', 'Minibar', 'Coffee maker', 'Room service'] as $f) {
            $facility = Roomfacilitydetails::firstOrCreate(['facilitytitle' => $f], ['facilitytypeid' => $type->facilitytypeid]);
            foreach ($out as $room) {
                RoomfailityRefAccomodation::firstOrCreate(['room_id' => $room->roomid, 'facilityid' => $facility->facilityid], ['facilititypeid' => $type->facilitytypeid]);
            }
        }
        foreach (['Breakfast' => 8, 'Airport pickup' => 25, 'Late check-out' => 20] as $svc => $rate) {
            foreach (array_keys($out) as $roomType) {
                TblComplementary::firstOrCreate(['complementaryname' => $svc, 'roomtype' => $roomType], ['rate' => $rate, 'status' => 1]);
            }
        }
        TblRoomOffer::firstOrCreate(['offertitle' => 'Deluxe weekend special'], ['roomid' => $out['Deluxe Double']->roomid, 'offer' => 10, 'offer_date' => today()->addDays(45)->toDateString(), 'offertext' => '10% off Deluxe Double rooms.']);
        foreach (['WELCOME10' => 10, 'SUMMER15' => 15, 'FAMILY20' => 20] as $code => $pct) {
            Promocode::firstOrCreate(['promocode' => $code], ['roomid' => 0, 'discount' => $pct, 'startdate' => today()->subDays(10)->toDateString(), 'enddate' => today()->addDays(90)->toDateString(), 'status' => 1]);
        }

        return $out;
    }

    /** @return list<Customerinfo> */
    private function customers(): array
    {
        $people = [
            ['Mr', 'Abin', 'Rahman', 'Male', '01715000000', 'Dhaka', 'Bangladesh'], ['Mr', 'Rusdi', 'Zaman', 'Male', '01717538180', 'Chattogram', 'Bangladesh'],
            ['Mr', 'Monir', 'Hossain', 'Male', '01717431840', 'Sylhet', 'Bangladesh'], ['Dr', 'Mosharrof', 'Hossain', 'Male', '01712772018', 'Dhaka', 'Bangladesh'],
            ['Ms', 'Shimanto', 'Akter', 'Female', '01715528252', 'Khulna', 'Bangladesh'], ['Mrs', 'Nusrat', 'Jahan', 'Female', '01811223344', 'Rajshahi', 'Bangladesh'],
            ['Mr', 'Tanvir', 'Ahmed', 'Male', '01911334455', 'Dhaka', 'Bangladesh'], ['Ms', 'Farhana', 'Islam', 'Female', '01611445566', 'Cumilla', 'Bangladesh'],
            ['Mr', 'John', 'Carter', 'Male', '447700900123', 'London', 'United Kingdom'], ['Ms', 'Emily', 'Stone', 'Female', '12025550143', 'New York', 'United States'],
            ['Mr', 'Hiroshi', 'Tanaka', 'Male', '819012345678', 'Tokyo', 'Japan'], ['Mrs', 'Priya', 'Sharma', 'Female', '919876543210', 'Kolkata', 'India'],
        ];
        $out = [];
        foreach ($people as $i => [$title, $first, $last, $gender, $phone, $city, $country]) {
            $c = Customerinfo::firstOrCreate(['cust_phone' => $phone], [
                'title' => $title, 'firstname' => $first, 'lastname' => $last, 'gender' => $gender, 'email' => strtolower($first.'.'.$last).'@example.com', 'city' => $city, 'country' => $country,
                'nationality' => $country, 'address' => $city, 'pitype' => $country === 'Bangladesh' ? 'NID' : 'Passport', 'pid' => (string) (1990000000 + $i * 7919), 'profession' => ['Engineer', 'Doctor', 'Teacher', 'Businessman'][$i % 4],
                'is_vip' => $i === 3, 'pass' => md5('guest1234'), 'balance' => 0, 'active' => 1, 'signupdate' => today()->subDays(60 - $i)->toDateString(),
            ]);
            $c->update(['customernumber' => 'C'.str_pad((string) $c->customerid, 5, '0', STR_PAD_LEFT)]);
            $out[] = $c;
        }
        TblWakeupCall::firstOrCreate(['custid' => $out[1]->customerid, 'wakeupcall_time' => today()->addDay()->setTime(6, 30)->format('Y-m-d H:i')], ['remarks' => 'Early flight']);

        return $out;
    }

    private function openingBalance(): void
    {
        $ledger = app(LedgerService::class);
        if ($ledger->balance('capital') == 0) {
            $ledger->post('journal', today()->subDays(60), [['account' => 'cash', 'debit' => 20000], ['account' => 'bank', 'debit' => 80000], ['account' => 'capital', 'credit' => 100000]], 'Opening capital', null, $this->uid);
        }
    }

    private function reservations(array $rooms, array $c): void
    {
        $svc = app(ReservationService::class);
        $pay = app(PaymentService::class);
        $cash = PaymentMethod::find(4);
        $bank = PaymentMethod::find(6);
        $d = fn (int $n) => Carbon::today()->addDays($n);

        // [guest, room type, check-in offset, nights, rooms, adults, children, source, promo?, flow, deposit]
        $plan = [
            [0, 'Standard Single', -14, 2, 1, 1, 0, 'phone', null, 'out', null],
            [1, 'Deluxe Double', -12, 3, 1, 2, 0, 'walk-in', null, 'out', null],
            [4, 'Deluxe Double', -9, 2, 2, 3, 1, 'agent', 'WELCOME10', 'out', null],
            [8, 'Family Suite', -7, 4, 1, 3, 1, 'email', null, 'out', null],
            [3, 'Presidential Suite', -2, 4, 1, 2, 0, 'phone', null, 'in', 400],
            [9, 'Deluxe Double', -1, 3, 1, 2, 0, 'walk-in', null, 'in', 150],
            [10, 'Standard Single', 0, 3, 1, 1, 0, 'email', null, 'in', 100],
            [5, 'Family Suite', 3, 3, 1, 4, 0, 'phone', 'SUMMER15', 'future', 200],
            [6, 'Deluxe Double', 5, 2, 1, 2, 0, 'agent', null, 'future', 100],
            [11, 'Presidential Suite', 9, 5, 1, 2, 1, 'email', null, 'future', 500],
            [7, 'Standard Single', 12, 2, 2, 2, 0, 'phone', null, 'future', null],
            [2, 'Deluxe Double', 4, 2, 1, 2, 0, 'phone', null, 'cancel', 100],
        ];
        $guestsFor = [3 => [['Mrs Ayesha Hossain', 'Female', '01712772019'], ['Master Ayan Hossain', 'Male', '']], 7 => [['Mr Faisal Ahmed', 'Male', '01811223355']], 4 => [['Ms Lina Ahmed', 'Female', '01911334456']]];

        foreach ($plan as $i => [$g, $type, $in, $nights, $n, $adults, $kids, $source, $promoCode, $flow, $deposit]) {
            $room = $rooms[$type];
            $checkin = $d($in);
            $promo = $promoCode ? Promocode::where('promocode', $promoCode)->first() : null;
            $booking = $svc->create($c[$g], $room, $checkin, $d($in + $nights), $n, $adults, $kids, null, $i % 3 === 0 ? 'High floor please' : null, $promo, $source, $this->uid,
                $deposit, $deposit ? ($i % 2 ? $bank : $cash) : null, ['booking_source' => ['Booking.com', 'Agoda', 'Corporate'][$i % 3], 'booking_source_no' => 'REF'.(1000 + $i), 'arrival_from' => ['Dhaka', 'Chattogram', 'Airport'][$i % 3], 'purpose' => ['Business', 'Holiday', 'Family visit'][$i % 3]]);
            foreach ($guestsFor[$i] ?? [] as [$name, $gender, $mobile]) {
                TblOtherguest::create(['bookedid' => (string) $booking->bookedid, 'booking_id' => $booking->bookedid, 'customerid' => $c[$g]->customerid, 'guestname' => $name, 'gender' => $gender, 'mobile' => $mobile, 'type' => 0]);
            }

            if ($flow === 'out') {
                $booking = $booking->fresh();
                if ($booking->balance > 0) {
                    $pay->receive($booking, $booking->balance, $i % 2 ? $bank : $cash, $this->uid, 'Settled at check-out');
                }
                $svc->checkIn($booking->fresh(), $this->uid);
                $svc->addCharge($booking->fresh(), 'Restaurant', 30 + $i * 5, $this->uid);
                $booking = $booking->fresh();
                $pay->receive($booking, $booking->balance, $cash, $this->uid, 'Extras');
                $svc->checkOut($booking->fresh(), $this->uid);
            } elseif ($flow === 'in') {
                $svc->checkIn($booking->fresh(), $this->uid);
                if ($i === 4) {
                    $svc->addCharge($booking->fresh(), 'Minibar', 45, $this->uid);
                }
            } elseif ($flow === 'cancel') {
                $svc->cancel($booking->fresh(), $this->uid, 50, $cash, 'Guest changed plans');
            }
        }
    }

    private function purchasing(): void
    {
        $inventory = app(InventoryService::class);
        $purchases = app(PurchaseService::class);

        $kg = Unit::firstOrCreate(['name' => 'Kilogram'], ['short_code' => 'kg', 'is_active' => true]);
        $pc = Unit::firstOrCreate(['name' => 'Piece'], ['short_code' => 'pc', 'is_active' => true]);
        $ltr = Unit::firstOrCreate(['name' => 'Litre'], ['short_code' => 'ltr', 'is_active' => true]);
        $pack = Unit::firstOrCreate(['name' => 'Pack'], ['short_code' => 'pack', 'is_active' => true]);
        $cats = [];
        foreach (['Toiletries', 'Linen', 'Cleaning', 'Kitchen', 'Minibar'] as $n) {
            $cats[$n] = ItemCategory::firstOrCreate(['name' => $n], ['is_active' => true]);
        }
        $items = [];
        foreach ([
            ['SOAP', 'Soap bar', 'Toiletries', $pc, 50], ['SHMP', 'Shampoo sachet', 'Toiletries', $pc, 100], ['TOWL', 'Bath towel', 'Linen', $pc, 30], ['BEDS', 'Bed sheet', 'Linen', $pc, 20],
            ['DTRG', 'Detergent', 'Cleaning', $kg, 15], ['FLCL', 'Floor cleaner', 'Cleaning', $ltr, 10], ['RICE', 'Rice', 'Kitchen', $kg, 50], ['OIL', 'Cooking oil', 'Kitchen', $ltr, 20],
            ['WATR', 'Mineral water', 'Minibar', $pack, 20], ['COLA', 'Soft drink', 'Minibar', $pack, 15],
        ] as [$sku, $name, $cat, $unit, $reorder]) {
            $items[$sku] = InventoryItem::firstOrCreate(['sku' => $sku], ['name' => $name, 'category_id' => $cats[$cat]->id, 'unit_id' => $unit->id, 'reorder_level' => $reorder, 'is_active' => true]);
        }
        $sup = [];
        foreach ([['SUP-0001', 'Dhaka Supplies Ltd', 'Mr Karim', '01710000001'], ['SUP-0002', 'Fresh Foods Co.', 'Ms Rina', '01710000002'], ['SUP-0003', 'Linen House', 'Mr Salam', '01710000003']] as [$code, $name, $person, $phone]) {
            $sup[$code] = Supplier::firstOrCreate(['code' => $code], ['name' => $name, 'contact_person' => $person, 'phone' => $phone, 'email' => strtolower(explode(' ', $name)[0]).'@supplier.test', 'address' => 'Dhaka', 'is_active' => true]);
        }
        $cash = LedgerAccount::where('system_key', 'cash')->first();
        $bank = LedgerAccount::where('system_key', 'bank')->first();

        $p1 = $purchases->receive($sup['SUP-0001'], today()->subDays(20)->toDateString(), [
            ['item' => $items['SOAP']->id, 'quantity' => 400, 'unit_cost' => 0.5], ['item' => $items['SHMP']->id, 'quantity' => 600, 'unit_cost' => 0.3], ['item' => $items['DTRG']->id, 'quantity' => 60, 'unit_cost' => 2.2], ['item' => $items['FLCL']->id, 'quantity' => 40, 'unit_cost' => 3],
        ], 20, 'INV-4411', 'Monthly housekeeping order', ['amount' => 500, 'account' => $bank->id], $this->uid);
        $purchases->pay($p1, $p1->fresh()->due, $bank->id, today()->subDays(10)->toDateString(), 'Cheque 1021', $this->uid);
        $purchases->receive($sup['SUP-0002'], today()->subDays(8)->toDateString(), [
            ['item' => $items['RICE']->id, 'quantity' => 200, 'unit_cost' => 0.9], ['item' => $items['OIL']->id, 'quantity' => 80, 'unit_cost' => 1.8], ['item' => $items['WATR']->id, 'quantity' => 60, 'unit_cost' => 4], ['item' => $items['COLA']->id, 'quantity' => 40, 'unit_cost' => 6],
        ], 0, 'FF-882', null, ['amount' => 300, 'account' => $cash->id], $this->uid);
        $p3 = $purchases->receive($sup['SUP-0003'], today()->subDays(3)->toDateString(), [
            ['item' => $items['TOWL']->id, 'quantity' => 100, 'unit_cost' => 4.5], ['item' => $items['BEDS']->id, 'quantity' => 60, 'unit_cost' => 9],
        ], 0, 'LH-310', 'Pay on delivery next week', null, $this->uid);
        $line = $p3->items()->first();
        $purchases->returnGoods($p3, [$line->id => 5], today()->subDay()->toDateString(), 'Stitching defect', $this->uid);

        $inventory->issue($items['SOAP']->fresh(), 80, 'Rooms restock', $this->uid);
        $inventory->issue($items['SHMP']->fresh(), 150, 'Rooms restock', $this->uid);
        $inventory->waste($items['WATR']->fresh(), 4, 'Expired', $this->uid);
        $inventory->waste($items['OIL']->fresh(), 2, 'Spilled', $this->uid);
    }

    private function hr(): void
    {
        $depts = [];
        foreach (['Front office', 'Housekeeping', 'Kitchen', 'Maintenance', 'Accounts', 'Security'] as $n) {
            $depts[$n] = HrDepartment::firstOrCreate(['name' => $n], ['is_active' => true]);
        }
        $pos = [];
        foreach ([['Receptionist', 'Front office'], ['Front office manager', 'Front office'], ['Room attendant', 'Housekeeping'], ['Housekeeping supervisor', 'Housekeeping'], ['Chef', 'Kitchen'], ['Technician', 'Maintenance'], ['Accountant', 'Accounts'], ['Security guard', 'Security']] as [$t, $dept]) {
            $pos[$t] = HrPosition::firstOrCreate(['title' => $t], ['department_id' => $depts[$dept]->id, 'is_active' => true]);
        }
        $annual = HrLeaveType::firstOrCreate(['name' => 'Annual leave'], ['days_per_year' => 14, 'is_paid' => true, 'is_active' => true]);
        HrLeaveType::firstOrCreate(['name' => 'Sick leave'], ['days_per_year' => 10, 'is_paid' => true, 'is_active' => true]);
        HrLeaveType::firstOrCreate(['name' => 'Unpaid leave'], ['days_per_year' => 0, 'is_paid' => false, 'is_active' => true]);
        HrHoliday::firstOrCreate(['holiday_date' => today()->addDays(20)->toDateString()], ['name' => 'National holiday']);

        $staff = [
            ['E-001', 'Rafiq', 'Islam', 'Male', 'Front office manager', 2200, 'Full time'], ['E-002', 'Sumaiya', 'Khan', 'Female', 'Receptionist', 900, 'Full time'],
            ['E-003', 'Jahid', 'Hasan', 'Male', 'Receptionist', 900, 'Full time'], ['E-004', 'Rokeya', 'Begum', 'Female', 'Housekeeping supervisor', 1100, 'Full time'],
            ['E-005', 'Maya', 'Akter', 'Female', 'Room attendant', 600, 'Full time'], ['E-006', 'Kabir', 'Mia', 'Male', 'Room attendant', 600, 'Contract'],
            ['E-007', 'Chef Anwar', 'Ali', 'Male', 'Chef', 1500, 'Full time'], ['E-008', 'Delwar', 'Hossain', 'Male', 'Technician', 800, 'Full time'],
            ['E-009', 'Shirin', 'Sultana', 'Female', 'Accountant', 1400, 'Full time'], ['E-010', 'Babul', 'Mia', 'Male', 'Security guard', 550, 'Part time'],
        ];
        $emps = [];
        foreach ($staff as $i => [$code, $first, $last, $gender, $title, $salary, $type]) {
            $e = HrEmployee::firstOrCreate(['code' => $code], [
                'first_name' => $first, 'last_name' => $last, 'gender' => $gender, 'email' => strtolower(str_replace(' ', '', $first)).'@grandpalace.test', 'phone' => '0171100'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'birth_date' => today()->subYears(24 + $i * 2)->toDateString(), 'join_date' => today()->subMonths(6 + $i * 3)->toDateString(), 'position_id' => $pos[$title]->id, 'department_id' => $pos[$title]->department_id,
                'basic_salary' => $salary, 'national_id' => (string) (1980000000 + $i * 331), 'bank_account' => '1000'.str_pad((string) $i, 6, '0', STR_PAD_LEFT), 'address' => 'Dhaka', 'is_active' => true,
                'father_name' => 'Md. '.$last.' Sr.', 'mother_name' => 'Mrs. '.$last, 'marital_status' => $i % 2 ? 'Married' : 'Single', 'blood_group' => ['A+', 'B+', 'O+', 'AB+'][$i % 4], 'religion' => 'Islam', 'nationality' => 'Bangladeshi',
                'present_address' => 'Mirpur, Dhaka', 'emergency_name' => 'Family contact', 'emergency_relation' => 'Brother', 'emergency_phone' => '0181200'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'employment_type' => $type, 'work_location' => 'Main building', 'bank_name' => 'City Bank', 'bank_branch' => 'Gulshan',
            ]);
            HrEmployeeEducation::firstOrCreate(['employee_id' => $e->id, 'degree' => 'Bachelor of Business Administration'], ['institute' => 'Dhaka University', 'passing_year' => 2012 + $i, 'result' => '3.4']);
            HrEmployeeExperience::firstOrCreate(['employee_id' => $e->id, 'company' => 'Sea Pearl Resort'], ['title' => $title, 'from_date' => today()->subYears(5)->toDateString(), 'to_date' => today()->subYears(1)->toDateString(), 'responsibilities' => 'Daily operations.']);
            if ($i < 3) {
                HrSalaryComponent::firstOrCreate(['employee_id' => $e->id, 'name' => 'Housing allowance'], ['kind' => 'allowance', 'amount' => 10, 'is_percent' => true]);
                HrSalaryComponent::firstOrCreate(['employee_id' => $e->id, 'name' => 'Provident fund'], ['kind' => 'deduction', 'amount' => 5, 'is_percent' => true]);
            }
            $emps[] = $e;
        }

        // Two weeks of attendance (working days only) with the odd late / absent day.
        foreach ($emps as $i => $e) {
            for ($n = 13; $n >= 1; $n--) {
                $day = today()->subDays($n);
                if (in_array($day->dayOfWeek, [5, 6], true)) {
                    continue;
                }
                $status = ($n + $i) % 11 === 0 ? 'absent' : (($n + $i) % 7 === 0 ? 'late' : 'present');
                HrAttendance::firstOrCreate(['employee_id' => $e->id, 'work_date' => $day->toDateString()], [
                    'status' => $status, 'check_in' => $status === 'absent' ? null : ($status === 'late' ? '09:40' : '08:55'), 'check_out' => $status === 'absent' ? null : '17:30',
                ]);
            }
        }

        $leave = app(LeaveService::class);
        $r = $leave->request($emps[1], $annual, today()->addDays(10), today()->addDays(12), 'Family trip');
        $leave->decide($r, true, $this->uid);
        $leave->request($emps[4], $annual, today()->addDays(15), today()->addDays(16), 'Personal');

        $bank = LedgerAccount::where('system_key', 'bank')->first();
        app(LoanService::class)->issue($emps[5], 600, 6, today()->subDays(5), today()->addMonth()->format('Y-m'), $bank->id, 'Medical expenses', $this->uid);
        HrAward::firstOrCreate(['employee_id' => $emps[2]->id, 'title' => 'Employee of the month'], ['cash_amount' => 100, 'awarded_on' => today()->subMonth()->endOfMonth()->toDateString(), 'note' => 'Great guest feedback']);
        HrCandidate::firstOrCreate(['name' => 'Samiul Haque'], ['email' => 'samiul@example.com', 'phone' => '01712340000', 'position_id' => $pos['Receptionist']->id, 'stage' => 'interview', 'interview_on' => today()->addDays(3)->toDateString(), 'rating' => 4]);
        HrCandidate::firstOrCreate(['name' => 'Tania Rahman'], ['email' => 'tania@example.com', 'phone' => '01712340001', 'position_id' => $pos['Chef']->id, 'stage' => 'applied']);

        // Last month's payroll, approved and paid.
        $payroll = app(PayrollService::class);
        $period = today()->subMonth()->format('Y-m');
        $run = $payroll->generate($period, $this->uid);
        $run = $payroll->finalize($run, $this->uid);
        $payroll->pay($run, $bank->id, today(), $this->uid);

        $shifts = [];
        foreach ([['Morning', '06:00', '14:00', '#0f766e'], ['Evening', '14:00', '22:00', '#2563eb'], ['Night', '22:00', '06:00', '#7c3aed']] as [$name, $from, $to, $color]) {
            $shifts[] = HrShift::firstOrCreate(['name' => $name], ['starts_at' => $from, 'ends_at' => $to, 'color' => $color, 'is_active' => true]);
        }
        foreach ($emps as $i => $e) {
            for ($n = 0; $n < 7; $n++) {
                $day = today()->addDays($n);
                $off = ($n + $i) % 7 === 6;
                HrRoster::firstOrCreate(['employee_id' => $e->id, 'work_date' => $day->toDateString()], ['shift_id' => $off ? null : $shifts[($i + intdiv($n, 3)) % 3]->id, 'created_by' => $this->uid]);
            }
        }
    }

    private function housekeeping(): void
    {
        foreach (['Change bed linen', 'Clean bathroom', 'Vacuum floor', 'Dust surfaces', 'Restock toiletries', 'Check minibar'] as $i => $name) {
            HkChecklistItem::firstOrCreate(['name' => $name], ['sort' => $i + 1, 'is_active' => true]);
        }
        $hk = app(HousekeepingService::class);
        $maid = HrEmployee::where('code', 'E-005')->first();
        $sup = HrEmployee::where('code', 'E-004')->first();
        $room = fn (int $no) => TblRoomnofloorassign::where('roomno', $no)->value('roomassignid');

        $hk->assign([$room(201), $room(202)], $maid?->id, today()->toDateString(), 'Checkout rooms first', 'staff', $this->uid);
        $hk->assign([$room(301)], $sup?->id, today()->toDateString(), null, 'staff', $this->uid);
        $hk->assign([$room(102)], null, today()->toDateString(), 'Requested by the guest via QR code.', 'qr');
        $task = \App\Models\HkTask::orderBy('id')->first();
        if ($task) {
            $hk->transition($task, 'start');
            $hk->transition($task, 'complete');
            $hk->transition($task->fresh(), 'inspect');
        }

        $costs = ['Shirt' => [1.5, 1, 3], 'Trousers' => [2, 1.5, 4], 'Suit' => [4, 3, 10], 'Dress' => [3, 2, 8], 'Bed sheet' => [2.5, 0, 0]];
        $products = [];
        foreach ($costs as $name => [$wash, $iron, $dry]) {
            $p = HkLaundryProduct::firstOrCreate(['name' => $name], ['is_active' => true]);
            $products[$name] = $p;
            HkLaundryCost::firstOrCreate(['product_id' => $p->id, 'service' => 'wash'], ['cost' => $wash]);
            if ($iron > 0) {
                HkLaundryCost::firstOrCreate(['product_id' => $p->id, 'service' => 'wash_iron'], ['cost' => $wash + $iron]);
            }
            if ($dry > 0) {
                HkLaundryCost::firstOrCreate(['product_id' => $p->id, 'service' => 'dry_clean'], ['cost' => $dry]);
            }
        }
        $laundry = app(LaundryService::class);
        $cash = LedgerAccount::where('system_key', 'cash')->first();
        $o1 = $laundry->create('Dr Mosharrof Hossain', '401', null, today()->toDateString(), [['product' => $products['Suit']->id, 'service' => 'dry_clean', 'quantity' => 2], ['product' => $products['Shirt']->id, 'service' => 'wash_iron', 'quantity' => 4]], null, $this->uid);
        $laundry->pay($o1, (float) $o1->total, $cash->id, today()->toDateString(), 'Cash', $this->uid);
        $o1->update(['status' => 'delivered']);
        $o2 = $laundry->create('Emily Stone', '203', null, today()->toDateString(), [['product' => $products['Dress']->id, 'service' => 'dry_clean', 'quantity' => 1], ['product' => $products['Trousers']->id, 'service' => 'wash_iron', 'quantity' => 2]], 'Handle with care', $this->uid);
        $laundry->pay($o2, 5, $cash->id, today()->toDateString(), 'Advance', $this->uid);
        $o2->update(['status' => 'washing']);
    }

    private function transport(array $c): void
    {
        $cars = [];
        foreach ([['DHA-11-2345', 'Car', 'Toyota Axio', 4, 'Abdul Karim', '01710101010', 15, 1.2], ['DHA-12-8890', 'Microbus', 'Toyota Hiace', 12, 'Sohel Rana', '01710101011', 30, 1.8], ['DHA-13-4521', 'SUV', 'Mitsubishi Pajero', 6, 'Jamal Uddin', '01710101012', 25, 1.6]] as [$reg, $type, $model, $seats, $driver, $phone, $base, $km]) {
            $cars[] = TrVehicle::firstOrCreate(['reg_no' => $reg], ['type' => $type, 'make_model' => $model, 'seats' => $seats, 'driver_name' => $driver, 'driver_phone' => $phone, 'base_fare' => $base, 'rate_per_km' => $km, 'is_active' => true]);
        }
        $f1 = TrFlight::firstOrCreate(['flight_no' => 'BG-147', 'guest_name' => 'John Carter'], ['direction' => 'arrival', 'airline' => 'Biman Bangladesh', 'airport' => 'Hazrat Shahjalal Intl.', 'flight_at' => today()->addDays(2)->setTime(14, 20), 'passengers' => 1]);
        $f2 = TrFlight::firstOrCreate(['flight_no' => 'EK-583', 'guest_name' => 'Emily Stone'], ['direction' => 'departure', 'airline' => 'Emirates', 'airport' => 'Hazrat Shahjalal Intl.', 'flight_at' => today()->addDays(4)->setTime(23, 45), 'passengers' => 2]);
        TrFlight::firstOrCreate(['flight_no' => 'US-212', 'guest_name' => 'Hiroshi Tanaka'], ['direction' => 'arrival', 'airline' => 'US-Bangla', 'airport' => 'Osmani Intl. (Sylhet)', 'flight_at' => today()->addDays(6)->setTime(9, 5), 'passengers' => 1]);
        foreach ([[$cars[0], $f1, 'John Carter', 2, 10, 'Airport', 'Grand Palace Hotel', 18], [$cars[1], $f2, 'Emily Stone', 4, 19, 'Grand Palace Hotel', 'Airport', 18], [$cars[2], null, 'Dr Mosharrof Hossain', 1, 9, 'Grand Palace Hotel', 'Gulshan Club', 6]] as [$car, $flight, $guest, $dayOffset, $hour, $from, $to, $km]) {
            $start = today()->addDays($dayOffset)->setTime($hour, 0);
            TrVehicleBooking::firstOrCreate(['vehicle_id' => $car->id, 'pickup_at' => $start], ['flight_id' => $flight?->id, 'guest_name' => $guest, 'pickup_location' => $from, 'drop_location' => $to, 'distance_km' => $km, 'amount' => round((float) $car->base_fare + (float) $car->rate_per_km * $km, 2), 'status' => 'booked']);
        }
    }

    private function halls(): void
    {
        $banquet = HallType::firstOrCreate(['name' => 'Banquet hall'], ['is_active' => true]);
        $conf = HallType::firstOrCreate(['name' => 'Conference room'], ['is_active' => true]);
        $fac = [];
        foreach (['Projector', 'Sound system', 'Stage', 'Air conditioning', 'Wi-Fi', 'Catering kitchen'] as $f) {
            $fac[$f] = HallFacility::firstOrCreate(['name' => $f], ['is_active' => true]);
        }
        $grand = Hall::firstOrCreate(['name' => 'Grand Ballroom'], ['type_id' => $banquet->id, 'capacity' => 300, 'rate_per_hour' => 150, 'rate_per_day' => 1000, 'location' => '1st floor', 'description' => 'Weddings and large banquets.', 'is_active' => true]);
        $board = Hall::firstOrCreate(['name' => 'Boardroom'], ['type_id' => $conf->id, 'capacity' => 20, 'rate_per_hour' => 40, 'rate_per_day' => 250, 'location' => '2nd floor', 'description' => 'Meetings and interviews.', 'is_active' => true]);
        $garden = Hall::firstOrCreate(['name' => 'Garden Pavilion'], ['type_id' => $banquet->id, 'capacity' => 120, 'rate_per_hour' => 90, 'rate_per_day' => 600, 'location' => 'Garden', 'description' => 'Open-air events.', 'is_active' => true]);
        $grand->facilities()->syncWithoutDetaching(array_map(fn ($f) => $f->id, [$fac['Projector'], $fac['Sound system'], $fac['Stage'], $fac['Air conditioning'], $fac['Catering kitchen']]));
        $board->facilities()->syncWithoutDetaching(array_map(fn ($f) => $f->id, [$fac['Projector'], $fac['Wi-Fi'], $fac['Air conditioning']]));
        $garden->facilities()->syncWithoutDetaching([$fac['Sound system']->id, $fac['Catering kitchen']->id]);
        $plans = [
            [$grand, 'Wedding banquet', 'banquet', 250, 25], [$grand, 'Conference theatre', 'theatre', 300, 0], [$board, 'Boardroom table', 'boardroom', 20, 1], [$board, 'U-shape', 'u_shape', 16, 1], [$garden, 'Cocktail party', 'cocktail', 120, 0],
        ];
        $seat = [];
        foreach ($plans as [$hall, $name, $layout, $seats, $tables]) {
            $seat[$name] = HallSeatPlan::firstOrCreate(['hall_id' => $hall->id, 'name' => $name], ['layout' => $layout, 'seats' => $seats, 'tables' => $tables]);
        }

        $svc = app(HallService::class);
        $cash = LedgerAccount::where('system_key', 'cash')->first();
        $bank = LedgerAccount::where('system_key', 'bank')->first();
        $b1 = $svc->create(['hall_id' => $grand->id, 'seat_plan_id' => $seat['Wedding banquet']->id, 'customer_name' => 'Rahman & Co.', 'phone' => '01711000111', 'event_name' => 'Wedding reception', 'event_date' => today()->addDays(12)->toDateString(), 'starts_at' => '18:00', 'ends_at' => '23:00', 'guests' => 240, 'status' => 'confirmed', 'discount' => 50], $this->uid);
        $svc->pay($b1, 300, $bank->id, today()->toDateString(), 'Advance', $this->uid);
        $b2 = $svc->create(['hall_id' => $board->id, 'seat_plan_id' => $seat['Boardroom table']->id, 'customer_name' => 'Delta Corp', 'phone' => '01711000222', 'email' => 'events@delta.test', 'event_name' => 'Quarterly meeting', 'event_date' => today()->addDays(3)->toDateString(), 'starts_at' => '10:00', 'ends_at' => '13:00', 'guests' => 14, 'status' => 'confirmed'], $this->uid);
        $svc->pay($b2, (float) $b2->total, $cash->id, today()->toDateString(), 'Paid in full', $this->uid);
        $svc->create(['hall_id' => $garden->id, 'seat_plan_id' => $seat['Cocktail party']->id, 'customer_name' => 'Ms Farzana', 'phone' => '01711000333', 'event_name' => 'Birthday party', 'event_date' => today()->addDays(20)->toDateString(), 'starts_at' => '17:00', 'ends_at' => '21:00', 'guests' => 80, 'status' => 'tentative'], $this->uid);
    }
}
