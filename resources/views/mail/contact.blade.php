<x-mail::message>
# New message from the website

**From:** {{ $contact->name }} ({{ $contact->email }}){{ $contact->phone ? ' · '.$contact->phone : '' }}
**Subject:** {{ $contact->subject ?: '—' }}

{{ $contact->message }}
</x-mail::message>
