<?php

namespace Tests\Feature;

use App\Models\Bedstype;
use App\Models\Customerinfo;
use App\Models\Page;
use App\Models\Promocode;
use App\Models\TblFloor;
use App\Models\RoomImage;
use App\Models\Roomfacilitydetails;
use App\Models\Roomfacilitytype;
use App\Models\RoomfailityRefAccomodation;
use App\Models\TblRoomnofloorassign;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ResourceTest extends HotelTestCase
{
    private function signInStaff(?string $role = null): User
    {
        if ($role) {
            $user = User::create(['firstname' => 'Role', 'lastname' => $role, 'email' => strtolower(str_replace(' ', '', $role)).'@example.com', 'password' => Hash::make('Passw0rd!'), 'status' => 1, 'usertype' => 1, 'is_admin' => 0]);
            $user->assignRole($role);
        } else {
            $user = $this->staff;
        }
        $this->actingAs($user, 'admin');

        return $user;
    }

    public function test_crud_cycle_for_a_simple_resource(): void
    {
        $this->signInStaff();

        $this->get('/admin/floors')->assertOk();
        $this->get('/admin/floors/create')->assertOk()->assertSee('Floor name');
        $this->post('/admin/floors', ['floorname' => 'First floor', 'status' => 1])->assertRedirect('/admin/floors');

        $floor = TblFloor::where('floorname', 'First floor')->firstOrFail();
        $this->get('/admin/floors')->assertSee('First floor');
        $this->get("/admin/floors/{$floor->floorid}/edit")->assertOk()->assertSee('First floor');
        $this->put("/admin/floors/{$floor->floorid}", ['floorname' => 'Mezzanine', 'status' => 0])->assertRedirect('/admin/floors');
        $this->assertSame('Mezzanine', $floor->fresh()->floorname);
        $this->assertSame(0, (int) $floor->fresh()->status);

        $this->delete("/admin/floors/{$floor->floorid}")->assertRedirect('/admin/floors');
        $this->assertNull(TblFloor::find($floor->floorid));
    }

    public function test_validation_and_uniqueness_are_enforced(): void
    {
        $this->signInStaff();
        Bedstype::create(['bedstypetitle' => 'Bunk']);

        $this->post('/admin/bed-types', ['bedstypetitle' => ''])->assertSessionHasErrors('bedstypetitle');
        $this->post('/admin/bed-types', ['bedstypetitle' => 'Bunk'])->assertSessionHasErrors('bedstypetitle');
        $this->post('/admin/bed-types', ['bedstypetitle' => 'Cot'])->assertSessionHasNoErrors();

        // Editing a record may keep its own unique value.
        $bunk = Bedstype::where('bedstypetitle', 'Bunk')->first();
        $this->put("/admin/bed-types/{$bunk->Bedstypeid}", ['bedstypetitle' => 'Bunk'])->assertSessionHasNoErrors();
    }

    public function test_unknown_resources_and_records_return_404(): void
    {
        $this->signInStaff();

        $this->get('/admin/not-a-resource')->assertNotFound();
        $this->get('/admin/floors/999999/edit')->assertNotFound();
    }

    public function test_room_type_with_rooms_cannot_be_deleted(): void
    {
        $this->signInStaff();

        $this->delete('/admin/room-types/'.$this->room->roomid)->assertSessionHasErrors('delete');
        $this->assertNotNull($this->room->fresh());
    }

    public function test_front_desk_role_is_limited(): void
    {
        $this->signInStaff('Front Desk');

        $this->get('/admin')->assertOk();
        $this->get('/admin/reservations')->assertOk();
        $this->get('/admin/customers')->assertOk();
        $this->get('/admin/floors')->assertForbidden();
        $this->get('/admin/taxes')->assertForbidden();
        $this->post('/admin/customers', [])->assertSessionHasErrors();
        $this->delete('/admin/customers/'.$this->guest->customerid)->assertForbidden();
        $this->get('/admin/payment-methods')->assertForbidden();
    }

    public function test_sidebar_only_lists_what_the_role_can_open(): void
    {
        $this->signInStaff('Front Desk');
        $this->get('/admin')->assertSee('Reservations')->assertDontSee('Taxes')->assertDontSee('Payment methods');
    }

    public function test_accountant_can_manage_taxes_but_not_rooms(): void
    {
        $this->signInStaff('Accountant');

        $this->get('/admin/taxes')->assertOk();
        $this->post('/admin/taxes', ['taxname' => 'City tax', 'rate' => 2.5, 'isactive' => 1])->assertRedirect('/admin/taxes');
        $this->get('/admin/room-types')->assertForbidden();
    }

    public function test_room_number_resource_manages_inventory(): void
    {
        $this->signInStaff();
        $floor = TblFloor::create(['floorname' => 'F1', 'status' => 1]);

        $this->post('/admin/rooms', ['roomno' => 101, 'roomid' => $this->room->roomid, 'floorid' => $floor->floorid, 'status' => 1])->assertSessionHasErrors('roomno');
        $this->post('/admin/rooms', ['roomno' => 103, 'roomid' => $this->room->roomid, 'floorid' => $floor->floorid, 'status' => 1])->assertRedirect('/admin/rooms');
        $this->assertSame(3, TblRoomnofloorassign::where('roomid', $this->room->roomid)->count());
    }

    public function test_promo_codes_are_normalised_and_unique(): void
    {
        $this->signInStaff();
        $payload = ['promocode' => 'summer10', 'roomid' => 0, 'discount' => 10, 'startdate' => now()->toDateString(), 'enddate' => now()->addMonth()->toDateString(), 'status' => 1];

        $this->post('/admin/promo-codes', $payload)->assertRedirect('/admin/promo-codes');
        $this->assertSame('SUMMER10', Promocode::firstOrFail()->promocode);
        $this->post('/admin/promo-codes', array_merge($payload, ['promocode' => 'SUMMER10']))->assertSessionHasErrors('promocode');
        $this->post('/admin/promo-codes', array_merge($payload, ['promocode' => 'late', 'enddate' => now()->subDay()->toDateString()]))->assertSessionHasErrors('enddate');
    }

    public function test_guest_records_get_a_number_and_hashed_password(): void
    {
        $this->signInStaff();

        $this->post('/admin/customers', ['firstname' => 'Walk', 'lastname' => 'In', 'email' => 'WalkIn@Example.com', 'cust_phone' => '0199999999', 'pass' => 'Secret123', 'active' => 1])->assertRedirect('/admin/customers');
        $guest = Customerinfo::where('email', 'walkin@example.com')->firstOrFail();
        $this->assertSame(str_pad((string) $guest->customerid, 4, '0', STR_PAD_LEFT), $guest->customernumber);
        $this->assertTrue(Hash::check('Secret123', $guest->pass));

        // Leaving the password blank on edit keeps it.
        $this->put("/admin/customers/{$guest->customerid}", ['firstname' => 'Walker', 'lastname' => 'In', 'email' => 'walkin@example.com', 'cust_phone' => '0199999999', 'pass' => '', 'active' => 1]);
        $this->assertTrue(Hash::check('Secret123', $guest->fresh()->pass));
        $this->assertSame('Walker', $guest->fresh()->firstname);
    }

    public function test_guests_with_bookings_cannot_be_deleted(): void
    {
        $this->signInStaff();
        $this->actingAs($this->guest, 'customer')->post('/book', $this->bookingPayload());
        auth('customer')->logout();
        $this->actingAs($this->staff, 'admin');

        $this->delete('/admin/customers/'.$this->guest->customerid)->assertSessionHasErrors('delete');
    }

    public function test_csv_export_neutralises_spreadsheet_formulas(): void
    {
        $this->signInStaff();
        Page::create(['slug' => 'x', 'title' => '=HYPERLINK("http://evil")', 'body' => 'b']);

        $csv = $this->get('/admin/pages?export=csv')->assertOk()->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString("\n=HYPERLINK", $csv);
    }

    public function test_pages_are_created_with_unique_slugs(): void
    {
        $this->signInStaff();

        $this->post('/admin/pages', ['title' => 'About us', 'slug' => '', 'body' => 'Hello', 'sort' => 1, 'published' => 1, 'show_in_menu' => 1])->assertRedirect('/admin/pages');
        $this->post('/admin/pages', ['title' => 'About us', 'slug' => '', 'body' => 'Again', 'sort' => 2, 'published' => 1])->assertRedirect('/admin/pages');

        $this->assertEqualsCanonicalizing(['about-us', 'about-us-2'], Page::pluck('slug')->all());
    }

    public function test_room_type_takes_many_facilities_and_many_photos_shown_on_the_website(): void
    {
        Storage::fake('public');
        $type = Roomfacilitytype::create(['facilitytypetitle' => 'Bathroom']);
        $tv = Roomfacilitytype::create(['facilitytypetitle' => 'Entertainment']);
        $shower = Roomfacilitydetails::create(['facilitytitle' => 'Rain shower', 'facilitytypeid' => $type->facilitytypeid]);
        $bath = Roomfacilitydetails::create(['facilitytitle' => 'Bathtub', 'facilitytypeid' => $type->facilitytypeid]);
        $smart = Roomfacilitydetails::create(['facilitytitle' => 'Smart TV', 'facilitytypeid' => $tv->facilitytypeid]);
        $this->actingAs($this->staff, 'admin');

        $fields = ['roomtype' => 'Garden View', 'bedstype' => 1, 'bedsno' => 1, 'capacity' => 2, 'roomsize' => 300, 'roomsizemesurement' => 'sqft', 'rate' => 100, 'bedcharge' => 0, 'personcharge' => 0, 'roomdescription' => 'Quiet', 'roomactive' => 1, 'exbedcapability' => 0, 'child_limit' => 0];
        $this->post('/admin/room-types', $fields + [
            'facility_ids' => [$shower->facilityid, $smart->facilityid],
            'gallery' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg'), UploadedFile::fake()->image('c.jpg')],
        ])->assertSessionHasNoErrors();
        $room = \App\Models\Roomdetails::where('roomtype', 'Garden View')->firstOrFail();
        $this->assertSame(2, RoomfailityRefAccomodation::where('room_id', $room->roomid)->count());
        $this->assertSame(3, RoomImage::where('room_id', $room->roomid)->count());
        $this->assertSame(1, RoomImage::where('room_id', $room->roomid)->where('sort_order', 0)->count());
        TblRoomnofloorassign::create(['roomid' => $room->roomid, 'floorid' => 1, 'roomno' => 901, 'status' => 1]);

        // Edit: add a facility, make the last photo the cover, remove the middle one, upload a fourth.
        $imgs = RoomImage::where('room_id', $room->roomid)->ordered()->get();
        $this->put('/admin/room-types/'.$room->roomid, $fields + [
            'facility_ids' => [$shower->facilityid, $bath->facilityid, $smart->facilityid],
            'cover_gallery' => $imgs[2]->room_img_id, 'remove_gallery' => [$imgs[1]->room_img_id],
            'gallery' => [UploadedFile::fake()->image('d.jpg')],
        ])->assertSessionHasNoErrors();
        $this->assertSame(3, RoomfailityRefAccomodation::where('room_id', $room->roomid)->count());
        $after = RoomImage::where('room_id', $room->roomid)->ordered()->get();
        $this->assertCount(3, $after);
        $this->assertSame($imgs[2]->room_img_id, $after->first()->room_img_id);
        $this->assertFalse($after->contains('room_img_id', $imgs[1]->room_img_id));

        // Website: every photo in the gallery, facilities grouped by type.
        $page = $this->get('/rooms/'.$room->roomid)->assertOk();
        foreach ($after as $img) {
            $page->assertSee($img->room_imagename);
        }
        $page->assertSee('Bathroom')->assertSee('Entertainment')->assertSee('Bathtub')->assertSee('Smart TV');

        // The room list uses the cover photo.
        $this->get('/rooms')->assertOk()->assertSee($after->first()->room_imagename);

        // Editing without touching the gallery keeps everything.
        $this->put('/admin/room-types/'.$room->roomid, $fields + ['facility_ids' => [$shower->facilityid]])->assertSessionHasNoErrors();
        $this->assertSame(1, RoomfailityRefAccomodation::where('room_id', $room->roomid)->count());
        $this->assertSame(3, RoomImage::where('room_id', $room->roomid)->count());
    }

    public function test_multi_item_lists_show_one_row_per_parent(): void
    {
        $this->actingAs($this->staff, 'admin');
        $type = Roomfacilitytype::create(['facilitytypetitle' => 'Bathroom']);
        $a = Roomfacilitydetails::create(['facilitytitle' => 'Rain shower', 'facilitytypeid' => $type->facilitytypeid]);
        $b = Roomfacilitydetails::create(['facilitytitle' => 'Bathtub', 'facilitytypeid' => $type->facilitytypeid]);
        foreach ([$a, $b] as $f) {
            RoomfailityRefAccomodation::create(['room_id' => $this->room->roomid, 'facilityid' => $f->facilityid, 'facilititypeid' => $type->facilitytypeid]);
        }
        RoomImage::create(['room_id' => $this->room->roomid, 'room_imagename' => 'storage/uploads/x1.jpg', 'sort_order' => 0]);
        RoomImage::create(['room_id' => $this->room->roomid, 'room_imagename' => 'storage/uploads/x2.jpg', 'sort_order' => 1]);
        \App\Models\TblComplementary::create(['roomtype' => 'Deluxe', 'complementaryname' => 'Breakfast', 'rate' => 500, 'status' => 1]);
        \App\Models\TblComplementary::create(['roomtype' => 'Deluxe', 'complementaryname' => 'Airport pickup', 'rate' => 1500, 'status' => 1]);

        $r = $this->get('/admin/room-facilities')->assertOk()->assertSee('Rain shower')->assertSee('Bathtub')->assertSee('Edit facilities');
        $this->assertSame(1, substr_count($r->getContent(), '<tr>') - 1); // header row + one room type
        $r = $this->get('/admin/room-images')->assertOk()->assertSee('Manage photos')->assertSee('Cover');
        $this->assertSame(2, substr_count($r->getContent(), 'storage/uploads/x'));
        $r = $this->get('/admin/services')->assertOk()->assertSee('Breakfast')->assertSee('Airport pickup');
        $this->assertSame(1, substr_count($r->getContent(), '<tr>') - 1);
        $this->get('/admin/room-facilities?q=zzzz')->assertOk()->assertSee('No room facilities found');
    }
}
