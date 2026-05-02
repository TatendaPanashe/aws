
@extends('layouts.main')

@section('title')
Welcome
@endsection

@section('content')
@include('includes.header')
@include('includes.sidebar')

<div class="card">
  <div class="card-header">
    Daily Collection Ammendment Form
  </div>
  <div class="card-body">
    @if (session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" id="submit" onsubmit="disableSubmitButton(this)" action="{{ route('dailycollection.ammendmentrequest') }}">
      @csrf
      <div class="row">
        <div class="form-group col-lg-4">
          <input type="hidden"  name="currencyold"  value="{{ $sql->currency }}">
          <label for="currency" class="form-label">Currency</label>
          <select name="currency" id="currency" class="form-control" required>
            <option value="USD_ZWG">USD & ZWG</option>
            <option value="USD">USD</option>
            <option value="ZWG">ZWG</option>
          </select>
        </div>

        <div class="form-group col-lg-4">
          <label class="form-label" for="insurance_transactions">USD Collections: {{ $sql->insurance_transactions }} 
            <input type="hidden"  name="useridold"  value="{{ $sql->user_id }}"> </label>
            <input type="hidden"  name="networkidold"  value="{{ $sql->networkid }}"> </label>
            <input type="hidden"  name="siteidold"  value="{{ $sql->siteid }}"> </label>

            <input type="hidden"  name="userid"  value="{{ $sql->user_id }}"> </label>
            <input type="hidden"  name="networkid"  value="{{ $sql->networkid }}"> </label>
            <input type="hidden"  name="siteid"  value="{{ $sql->siteid }}"> </label>


            <input type="hidden"  name="insurance_transactionsold"  value="{{ $sql->insurance_transactions }}"> </label>
            <input type="hidden"  name="transaction_id"  value="{{ $sql->id }}"> </label>
            <input type="hidden"  name="transaction_date"  value="{{ $sql->created_at }}"> </label>
          <input type="number" class="form-control" name="insurance_transactions" id="insurance_transactions"
                 value="{{ $sql->insurance_transactions }}" readonly required step="0.01">
        </div>
        
        <div class="form-group col-lg-4">
          <label class="form-label" for="zwg_insurance_transactions">ZWG Collections: {{ $sql->zwg_insurance_transactions }}.
             <input type="hidden"  name="zwg_nsurance_transactionsold"  value="{{ $sql->zwg_insurance_transactions }}"></label>
          <input type="number" class="form-control" name="zwg_insurance_transactions" id="zwg_insurance_transactions"
                 value="{{ $sql->zwg_insurance_transactions }}" readonly required step="0.01">
        </div>
      </div>

      <br>

      <div id="currency-fields">
        {{-- USD Breakdown --}}
        <div id="usd-breakdown" class="currency-field">
          <h3>USD Breakdown</h3>
          <div class="row">
            <div class="col-lg-2">
              <div class="form-group">
                <label class="form-label" for="usd_cash">Cash: {{ $sql->usd_cash }}
                   <input type="hidden"  name="usd_cashold"  value="{{ $sql->usd_cash }}"></label>
                <input type="number" class="form-control" name="usd_cash" id="usd_cash"
                       value="{{ $sql->usd_cash }}" step="0.01">
              </div>
            </div>

            <div class="col-lg-2">
              <div class="form-group">
                <label class="form-label" for="usd_swipe">Swipe: {{ $sql->usd_swipe }} 
                  <input type="hidden"  name="usd_swipeold"  value="{{ $sql->usd_swipe }}"></label>
                <input type="number" class="form-control" name="usd_swipe" id="usd_swipe"
                       value="{{ $sql->usd_swipe }}" step="0.01">
              </div>
            </div>

            <div class="col-lg-2">
              <div class="form-group">
                <label class="form-label" for="usd_transfers">Transfers: {{ $sql->usd_transfers }} 
                  <input type="hidden"  name="usd_transfersold"  value="{{ $sql->usd_transfers }}"></label>
                <input type="number" class="form-control" name="usd_transfers" id="usd_transfers"
                       value="{{ $sql->usd_transfers }}" step="0.01">
              </div>
            </div>

            <div class="form-group col-lg-3">
              <label class="form-label" for="usd_mpos">MPOS: {{ $sql->usd_mpos }} 
                <input type="hidden"  name="usd_mposold"  value="{{ $sql->usd_mpos }}"></label>
              <input type="number" class="form-control" name="usd_mpos" id="usd_mpos"
                     value="{{ $sql->usd_mpos }}" step="0.01">
            </div>

            <div class="form-group col-lg-3">
              <label class="form-label" for="usd_credit_sales">Credit Sales: {{ $sql->usd_credit_sales }}
                 <input type="hidden"  name="usd_credit_salesold"  value="{{ $sql->usd_credit_sales }}"></label>
              <input type="number" class="form-control" name="usd_credit_sales" id="usd_credit_sales"
                     value="{{ $sql->usd_credit_sales }}" step="0.01" readonly>
            </div>
          </div>

          <div class="row">
            <div class="form-group col-lg-2">
              <label class="form-label" for="third_party_premiums">3rd Party Premiums: {{ $sql->third_party_premiums }} 
                <input type="hidden"  name="third_party_premiumsold"  value="{{ $sql->third_party_premiums }}"></label>
              <input type="number" class="form-control" name="third_party_premiums" id="third_party_premiums"
                     value="{{ $sql->third_party_premiums }}" step="0.01">
            </div>

            <div class="form-group col-lg-2">
              <label class="form-label" for="full_cover_premiums">Full Cover Premiums: {{ $sql->full_cover_premiums }}
                 <input type="hidden"  name="full_cover_premiumsold"  value="{{ $sql->full_cover_premiums }}"></label>
              <input type="number" class="form-control" name="full_cover_premiums" id="full_cover_premiums"
                     value="{{ $sql->full_cover_premiums }}" step="0.01">
            </div>

            <div class="form-group col-lg-2">
              <label class="form-label" for="zinara_fees">Zinara Fees: {{ $sql->zinara_fees }} 
                <input type="hidden"  name="zinara_feesold"  value="{{ $sql->zinara_fees }}"></label>
              <input type="number" class="form-control" name="zinara_fees" id="zinara_fees"
                     value="{{ $sql->zinara_fees }}" step="0.01">
            </div>

            <div class="form-group col-lg-2">
              <label class="form-label" for="usd_debit_sales">Debit Sales: {{ $sql->usd_debit_sales }}
                 <input type="hidden"  name="usd_debit_salesold"  value="{{ $sql->usd_debit_sales }}"></label>
              <input type="number" class="form-control" name="usd_debit_sales" id="usd_debit_sales"
                     value="{{ $sql->usd_debit_sales }}" step="0.01" readonly>
            </div>

            <div class="col-lg-3">
              <label class="form-label" for="usd_total_deposited">Cash Deposited: {{ $sql->usd_total_deposited }} 
                <input type="hidden"  name="usd_total_depositedold"  value="{{ $sql->usd_total_deposited }}"></label>
              <input type="number" class="form-control" name="usd_total_deposited" id="usd_deposited_cash"
                     value="{{ $sql->usd_total_deposited }}" step="0.01">
            </div>
          </div>
        </div>

        <br>

        {{-- ZWG Breakdown --}}
        <div id="zwg-breakdown" class="currency-field">
          <h3>ZWG Breakdown</h3>
          <div class="row">
            <div class="col-lg-2">
              <div class="form-group">
                <label class="form-label" for="zwg_cash">Cash: {{ $sql->zwg_cash }} 
                  <input type="hidden"  name="zwg_cashold"  value="{{ $sql->zwg_cash }}"></label>
                <input type="number" class="form-control" name="zwg_cash" id="zwg_cash"
                       value="{{ $sql->zwg_cash }}" step="0.01">
              </div>
            </div>

            <div class="col-lg-2">
              <div class="form-group">
                <label class="form-label" for="zwg_swipe">Swipe: {{ $sql->zwg_swipe }} 
                  <input type="hidden"  name="zwg_swipeold"  value="{{ $sql->zwg_swipe }}"></label>
                <input type="number" class="form-control" name="zwg_swipe" id="zwg_swipe"
                       value="{{ $sql->zwg_swipe }}" step="0.01">
              </div>
            </div>

            <div class="col-lg-2">
              <div class="form-group">
                <label class="form-label" for="zwg_transfers">Transfers: {{ $sql->zwg_transfers }} 
                  <input type="hidden"  name="zwg_transfersold"  value="{{ $sql->zwg_transfers }}"></label>
                <input type="number" class="form-control" name="zwg_transfers" id="zwg_transfers"
                       value="{{ $sql->zwg_transfers }}" step="0.01">
              </div>
            </div>

            <div class="col-lg-3">
              <div class="form-group">
                <label class="form-label" for="zwg_mpos">MPOS: {{ $sql->zwg_mpos }} 
                  <input type="hidden"  name="zwg_mposold"  value="{{ $sql->zwg_mpos }}"></label>
                <input type="number" class="form-control" name="zwg_mpos" id="zwg_mpos"
                       value="{{ $sql->zwg_mpos }}" step="0.01">
              </div>
            </div>

            <div class="col-lg-3">
              <div class="form-group">
                <label class="form-label" for="zwg_credit_sales">Credit Sales: {{ $sql->zwg_credit_sales }} 
                  <input type="hidden"  name="zwg_credit_salesold"  value="{{ $sql->zwg_credit_sales }}"></label>
                <input type="number" class="form-control" name="zwg_credit_sales" id="zwg_credit_sales"
                       value="{{ $sql->zwg_credit_sales }}" step="0.01" readonly>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="form-group col-lg-2">
              <label class="form-label" for="zwg_third_party_premiums">3rd Party Premiums: {{ $sql->zwg_third_party_premiums }} 
                <input type="hidden"  name="zwg_third_party_premiumsold"  value="{{ $sql->zwg_third_party_premiums }}"></label>
              <input type="number" class="form-control" name="zwg_third_party_premiums" id="zwg_third_party_premiums"
                     value="{{ $sql->zwg_third_party_premiums }}" step="0.01">
            </div>

            <div class="form-group col-lg-2">
              <label class="form-label" for="zwg_full_cover_premiums">Full Cover Premiums: {{ $sql->zwg_full_cover_premiums }}
                 <input type="hidden"  name="zwg_full_cover_premiumsold"  value="{{ $sql->zwg_full_cover_premiums }}"></label>
              <input type="number" class="form-control" name="zwg_full_cover_premiums" id="zwg_full_cover_premiums"
                     value="{{ $sql->zwg_full_cover_premiums }}" step="0.01">
            </div>

            <div class="form-group col-lg-2">
              <label class="form-label" for="zwg_zinara_fees">Zinara Fees: {{ $sql->zwg_zinara_fees }} 
                <input type="hidden"  name="zwg_zinara_feesold"  value="{{ $sql->zwg_zinara_fees }}"></label>
              <input type="number" class="form-control" name="zwg_zinara_fees" id="zwg_zinara_fees"
                     value="{{ $sql->zwg_zinara_fees }}" step="0.01">
            </div>

            <div class="form-group col-lg-2">
              <label class="form-label" for="zwg_debit_sales">Debit Sales: {{ $sql->zwg_debit_sales }}
                 <input type="hidden"  name="zwg_debit_salesold"  value="{{ $sql->zwg_debit_sales }}"></label>
              <input type="number" class="form-control" name="zwg_debit_sales" id="zwg_debit_sales"
                     value="{{ $sql->zwg_debit_sales }}" step="0.01" readonly>
            </div>

            <div class="col-lg-3">
              <label class="form-label" for="zwg_total_deposited">Cash Deposited: {{ $sql->zwg_total_deposited }}
                 <input type="hidden"  name="zwg_total_depositedold"  value="{{ $sql->zwg_total_deposited }}"></label>
              <input type="number" class="form-control" name="zwg_total_deposited" id="zwg_deposited_cash"
                     value="{{ $sql->zwg_total_deposited }}" step="0.01">
            </div>
          </div>
        </div>
      </div>

      <br>

      <div class="row">
        <div class="col-lg-4">
          <div class="form-group">
            <input type="hidden"  name="bankold"  value="{{ $sql->bank }}">
            <label class="form-label" for="bank">Bank: {{ $sql->bank }}</label>
            <select class="form-control" name="bank" id="bank" required>
              <option value="{{ $sql->bank }}" selected>{{ $sql->bank }}</option>
              <option value="POSB">POSB</option>
              <option value="CBZ">CBZ</option>
              <option value="Ecocash">Ecocash</option>
              <option value="FBC">FBC</option>
              <option value="BancAbc">BancAbc</option>
              <option value="NMB">NMB</option>
              <option value="Post Money">Post Money</option>
              <option value="Innbucks">Innbucks</option>
              <option value="CBZ GRUMA">CBZ GRUMA</option>
              <option value="CBZ Blue Streak">CBZ Blue Streak</option>
              <option value="Nedbank">Nedbank</option>
              <option value="HQ Cash">HQ Cash</option>
            </select>
          </div>
        </div>
      </div>

      <br>

      <div class="col-lg-12">
        <div class="form-group">

            <input type="hidden"  name="commentsold"  value="{{ $sql->comments }}">
          <label class="form-label" for="comments">Comments: {{ $sql->comments }} </label><br>
          <label class="form-label" for="comments">Write new comment below -> </label>
          <textarea class="form-control" name="comments" id="comments">{{ $sql->comments }}</textarea>
        </div>
      </div>

      <br><br>
      <div class="form-group">
        <button id="submit-button" type="submit" class="btn btn-primary">Submit</button>
      </div>  
    </form>
  </div>
</div>

<script>


  document.getElementById('currency').addEventListener('change', function() {
    var usdBreakdown = document.getElementById('usd-breakdown');
    var zwgBreakdown = document.getElementById('zwg-breakdown');

    if (this.value === 'USD') {
      usdBreakdown.style.display = 'block';
      zwgBreakdown.style.display = 'none';
    } else if (this.value === 'ZWG') {
      usdBreakdown.style.display = 'none';
      zwgBreakdown.style.display = 'block';
    } else if (this.value === 'USD_ZWG') {
      usdBreakdown.style.display = 'block';
      zwgBreakdown.style.display = 'block';
    } else {
      usdBreakdown.style.display = 'none';
      zwgBreakdown.style.display = 'none';
    }
  });

document.addEventListener('DOMContentLoaded', () => {
    // USD Insurance Breakdown Inputs
    const thirdPartyPremiumsInput = document.getElementById('third_party_premiums');
    const fullCoverPremiumsInput = document.getElementById('full_cover_premiums');
    const zinaraFeesInput = document.getElementById('zinara_fees');
    const insuranceTransactionInput = document.getElementById('insurance_transactions');

    // ZWG Insurance Breakdown Inputs
    const zwgThirdPartyPremiumsInput = document.getElementById('zwg_third_party_premiums');
    const zwgFullCoverPremiumsInput = document.getElementById('zwg_full_cover_premiums');
    const zwgZinaraFeesInput = document.getElementById('zwg_zinara_fees');
    const zwgInsuranceTransactionInput = document.getElementById('zwg_insurance_transactions');

    // Calculate USD Insurance Transaction
    function calculateInsuranceTransaction() {
        const thirdPartyPremiums = parseFloat(thirdPartyPremiumsInput.value) || 0;
        const fullCoverPremiums = parseFloat(fullCoverPremiumsInput.value) || 0;
        const zinaraFees = parseFloat(zinaraFeesInput.value) || 0;
        const total = thirdPartyPremiums + fullCoverPremiums + zinaraFees;
        insuranceTransactionInput.value = total.toFixed(2);
    }

    // Calculate ZWG Insurance Transaction
    function calculateZwgInsuranceTransaction() {
        const zwgThirdPartyPremiums = parseFloat(zwgThirdPartyPremiumsInput.value) || 0;
        const zwgFullCoverPremiums = parseFloat(zwgFullCoverPremiumsInput.value) || 0;
        const zwgZinaraFees = parseFloat(zwgZinaraFeesInput.value) || 0;
        const total = zwgThirdPartyPremiums + zwgFullCoverPremiums + zwgZinaraFees;
        zwgInsuranceTransactionInput.value = total.toFixed(2);
    }

    // Event listeners for USD breakdown inputs
    thirdPartyPremiumsInput.addEventListener('input', calculateInsuranceTransaction);
    fullCoverPremiumsInput.addEventListener('input', calculateInsuranceTransaction);
    zinaraFeesInput.addEventListener('input', calculateInsuranceTransaction);

    // Event listeners for ZWG breakdown inputs
    zwgThirdPartyPremiumsInput.addEventListener('input', calculateZwgInsuranceTransaction);
    zwgFullCoverPremiumsInput.addEventListener('input', calculateZwgInsuranceTransaction);
    zwgZinaraFeesInput.addEventListener('input', calculateZwgInsuranceTransaction);
});

document.addEventListener('DOMContentLoaded', () => {
    // USD Fields
    const usdCashInput = document.getElementById('usd_cash');
    const usdSwipeInput = document.getElementById('usd_swipe');
    const usdTransfersInput = document.getElementById('usd_transfers');
    const usdThirdPartyPremiumsInput = document.getElementById('third_party_premiums');
    const usdFullCoverPremiumsInput = document.getElementById('full_cover_premiums');
    const usdZinaraFeesInput = document.getElementById('zinara_fees');
    const usdDebitSalesInput = document.getElementById('usd_debit_sales');
    const usdCreditSalesInput = document.getElementById('usd_credit_sales');
    const usdMPOS = document.getElementById('usd_mpos');

    // ZWG Fields
    const zwgCashInput = document.getElementById('zwg_cash');
    const zwgSwipeInput = document.getElementById('zwg_swipe');
    const zwgTransfersInput = document.getElementById('zwg_transfers');
    const zwgThirdPartyPremiumsInput = document.getElementById('zwg_third_party_premiums');
    const zwgFullCoverPremiumsInput = document.getElementById('zwg_full_cover_premiums');
    const zwgZinaraFeesInput = document.getElementById('zwg_zinara_fees');
    const zwgDebitSalesInput = document.getElementById('zwg_debit_sales');
    const zwgCreditSalesInput = document.getElementById('zwg_credit_sales');
    const zwgMPOS = document.getElementById('zwg_mpos');

    // Function to calculate USD sales
    function calculateUsdSales() {
        // Calculate b (cash + swipe + transfers)
        const b = (parseFloat(usdCashInput.value) || 0) + 
                 (parseFloat(usdSwipeInput.value) || 0) + 
                 (parseFloat(usdTransfersInput.value) || 0) + 
                 (parseFloat(usdMPOS.value) || 0);
                
        
        // Calculate a (third party + full cover + zinara fees)
        const a = (parseFloat(usdThirdPartyPremiumsInput.value) || 0) + 
                 (parseFloat(usdFullCoverPremiumsInput.value) || 0) + 
                 (parseFloat(usdZinaraFeesInput.value) || 0);
        
        const difference = b - a;
        
        if (difference > 0) {
            // b > a → positive difference → debit sales
            usdDebitSalesInput.value = difference.toFixed(2);
            usdCreditSalesInput.value = '0.00';
        } else if (difference < 0) {
            // b < a → negative difference → credit sales
            usdCreditSalesInput.value = Math.abs(difference).toFixed(2);
            usdDebitSalesInput.value = '0.00';
        } else {
            // equal
            usdDebitSalesInput.value = '0.00';
            usdCreditSalesInput.value = '0.00';
        }
    }

    // Function to calculate ZWG sales
    function calculateZwgSales() {
        // Calculate b (cash + swipe + transfers)
        const b = (parseFloat(zwgCashInput.value) || 0) + 
                 (parseFloat(zwgSwipeInput.value) || 0) + 
                 (parseFloat(zwgTransfersInput.value) || 0)+
                 (parseFloat(zwgMPOS.value) || 0);
        
        // Calculate a (third party + full cover + zinara fees)
        const a = (parseFloat(zwgThirdPartyPremiumsInput.value) || 0) + 
                 (parseFloat(zwgFullCoverPremiumsInput.value) || 0) + 
                 (parseFloat(zwgZinaraFeesInput.value) || 0);
        
        const difference = b - a;
        
        if (difference > 0) {
            // b > a → positive difference → debit sales
            zwgDebitSalesInput.value = difference.toFixed(2);
            zwgCreditSalesInput.value = '0.00';
        } else if (difference < 0) {
            // b < a → negative difference → credit sales
            zwgCreditSalesInput.value = Math.abs(difference).toFixed(2);
            zwgDebitSalesInput.value = '0.00';
        } else {
            // equal
            zwgDebitSalesInput.value = '0.00';
            zwgCreditSalesInput.value = '0.00';
        }
    }

    // USD Event listeners
    usdCashInput.addEventListener('input', calculateUsdSales);
    usdSwipeInput.addEventListener('input', calculateUsdSales);
    usdTransfersInput.addEventListener('input', calculateUsdSales);
    usdMPOS.addEventListener('input', calculateUsdSales);
    usdThirdPartyPremiumsInput.addEventListener('input', calculateUsdSales);
    usdFullCoverPremiumsInput.addEventListener('input', calculateUsdSales);
    usdZinaraFeesInput.addEventListener('input', calculateUsdSales);

    // ZWG Event listeners
    zwgCashInput.addEventListener('input', calculateZwgSales);
    zwgSwipeInput.addEventListener('input', calculateZwgSales);
    zwgTransfersInput.addEventListener('input', calculateZwgSales);
    zwgMPOS.addEventListener('input', calculateZwgSales);
    zwgThirdPartyPremiumsInput.addEventListener('input', calculateZwgSales);
    zwgFullCoverPremiumsInput.addEventListener('input', calculateZwgSales);
    zwgZinaraFeesInput.addEventListener('input', calculateZwgSales);
});

const submitButton = document.getElementById('submit-button');

// function checkTime() {
//     const currentTime = new Date().getHours();
//     if (currentTime >= 11 && currentTime < 22) { // Between 11 AM and 8 PM
//         submitButton.style.display = 'block';
//     } else {
//         submitButton.style.display = 'none';
//     }
// }

// // Check the time every minute
// setInterval(checkTime, 60000);

// // Check the time immediately
// checkTime();
</script>


<script>
function disableSubmitButton(form) {
    const button = form.querySelector('button[type="submit"]');
    if (button) {
        button.disabled = true;
        button.innerText = 'Saving...'; // optional: visual feedback
    }
    return true; // allows form to continue submitting
}
</script>

@endsection

