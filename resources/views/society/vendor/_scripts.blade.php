{{-- jQuery is loaded after the page content by layouts.app, so this goes in the 'scripts' section --}}
<script>
    window.vendorUrls = {
        detailForm: @json(route('society.vendor.detailForm')),
        saveDetail: @json(route('society.vendor.saveDetail')),
        saveFacility: @json(route('society.vendor.saveFacility')),
        vendorList: @json(route('society.vendor.vendorList')),
        billingForm: @json(route('society.vendor.billingForm')),
        saveBill: @json(route('society.vendor.saveBill')),
        printBill: @json(route('society.vendor.printBill', ['vendorBillId' => '__ID__'])),
        particularHeads: @json(route('society.vendor.particularHeads')),
        subCategories: @json(route('society.vendor.subCategories')),
        accountCategories: @json(route('society.vendor.accountCategories')),
        accountHeads: @json(route('society.vendor.accountHeads')),
        saveLedgerHeadInline: @json(route('society.vendor.saveLedgerHeadInline')),
    };
</script>
<script src="{{ asset('js/vendor_billing.js') }}?v={{ filemtime(public_path('js/vendor_billing.js')) }}"></script>
