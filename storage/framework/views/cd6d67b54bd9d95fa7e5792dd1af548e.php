<?php $__env->startSection('title'); ?>
Welcome
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('includes.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('includes.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<div class="pagetitle">
  <h1>Daily Collection Capture</h1>
  <p>Record branch activity with the full payment breakdown, premium mix, deposit details, and cash position for the day.</p>
</div>

<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span>Daily Collection Form</span>
      <span class="soft-chip"><i class="bi bi-info-circle"></i> USD and ZWG supported</span>
    </div>
  </div>
  <div class="card-body">
  <div class="glass-note mb-4">
    Fill in the actual premium activity first, then confirm deposits and bank selection. Totals for USD and ZWG are calculated directly from the entered breakdown.
  </div>
  <?php if(session('status')): ?>
            <div class="alert alert-success">
                <?php echo e(session('status')); ?>

            </div>
        <?php endif; ?>

        <?php if(session('error')): ?>
            <div class="alert alert-danger">
                <?php echo e(session('error')); ?>

            </div>
        <?php endif; ?>
    <form method="POST" id="collectionForm" action="<?php echo e(route('postcollection')); ?>">
      <?php echo csrf_field(); ?>
      <div class="row">
        <div class="form-group col-lg-4">
          <label for="currency" class="form-label">Currency:</label>
          <select name="currency" id="currency" class="form-control" required="required">
              <option value="USD_ZWG">USD & ZWG</option>
            <option value="USD">USD</option>
            <option value="ZWG">ZWG</option>
            
          </select>
        </div>

        <div class="form-group col-lg-4">
          <label class="form-label" for="insurance_transactions">USD Collections:</label>
          <input type="number" class="form-control" name="insurance_transactions" id="insurance_transactions" readonly required="required" step="0.01">
        </div>
        
         <div class="form-group col-lg-4">
          <label class="form-label" for="insurance_transactions">ZWG Collections:</label>
          <input type="number" class="form-control" name="zwg_insurance_transactions" id="zwg_insurance_transactions" readonly required="required" step="0.01">
        </div>
      </div>
      <br>
      
     

      <div id="currency-fields">
        <div id="usd-breakdown" class="currency-field">
          <h3>USD Breakdown</h3>
          <div class="row">
            <div class="col-lg-2">
              <div class="form-group">
                <label class="form-label" for="usd_cash">Cash:</label>
                <input type="number" class="form-control" name="usd_cash" id="usd_cash" step="0.01">
              </div>
            </div>
            <div class="col-lg-2">
              <div class="form-group">
                <label class="form-label" for="usd_swipe">Swipe:</label>
                <input type="number" class="form-control" name="usd_swipe" id="usd_swipe" step="0.01">
              </div>
            </div>
            <div class="col-lg-2">
              <div class="form-group">
                <label class="form-label" for="usd_transfers">Transfers:</label>
                <input type="number" class="form-control" name="usd_transfers" id="usd_transfers" step="0.01">
              </div>
            </div>

            
    <div class="form-group col-lg-2">
  <label class="form-label" for="usd_mpos">MPOS:</label>
  <input type="number" class="form-control" name="usd_mpos" id="usd_mpos" step="0.01">
</div>
        
            <div class="form-group col-lg-3">
  <label class="form-label" for="usd_credit_sales">Credit Sales:</label>
  <input type="number" class="form-control" name="usd_credit_sales" id="usd_credit_sales" step="0.01" readonly>
  </div>
            
            
            <div class="row">
        <div class="form-group col-lg-2">
          <label class="form-label" for="third_party_premiums">3rd Party Premiums:</label>
          <input type="number" class="form-control" name="third_party_premiums" id="third_party_premiums" step="0.01">
        </div>

        <div class="form-group col-lg-2">
          <label class="form-label" for="full_cover_premiums">Full Cover Premiums:</label>
          <input type="number" class="form-control" name="full_cover_premiums" id="full_cover_premiums" step="0.01">
        </div>

        <div class="form-group col-lg-2">
          <label class="form-label" for="zinara_fees">Zinara Fees:</label>
          <input type="number" class="form-control" name="zinara_fees" id="zinara_fees" step="0.01">
        </div>
        <div class="form-group col-lg-2">
  <label class="form-label" for="other_insurances_usd">Other USD Insurances:</label>
  <input type="number" class="form-control" name="other_insurances_usd" id="other_insurances_usd" step="0.01" >
</div>
        <div class="form-group col-lg-2">
  <label class="form-label" for="usd_debit_sales">Debit Sales:</label>
  <input type="number" class="form-control" name="usd_debit_sales" id="usd_debit_sales" step="0.01" readonly>
</div>
<div class="col-lg-2">
        <label class="form-label" for="deposited_cash">Cash Deposited:</label>
        <input type="number" class="form-control" name="usd_total_deposited" id="usd_deposited_cash" step="0.01">
</div>

            </div>
          </div>
        </div>

        <br>

        <div id="zwg-breakdown"class="currency-field">
          <h3>ZWG Breakdown</h3>
          <div class="row">
            <div class="col-lg-2">
              <div class="form-group">
                <label class="form-label" for="zwg_cash">Cash:</label>
                <input type="number" class="form-control" name="zwg_cash" id="zwg_cash" step="0.01">
              </div>
            </div>
            <div class="col-lg-2">
              <div class="form-group">
                <label class="form-label" for="zwg_swipe">Swipe:</label>
                <input type="number" class="form-control" name="zwg_swipe" id="zwg_swipe" step="0.01">
              </div>
            </div>
            <div class="col-lg-2">
              <div class="form-group">
                <label class="form-label" for="zwg_transfers">Transfers:</label>
                <input type="number" class="form-control" name="zwg_transfers" id="zwg_transfers" step="0.01">
              </div>
            </div>
             <div class="col-lg-2">
              <div class="form-group">
                <label class="form-label" for="zwg_transfers">MPOS:</label>
                <input type="number" class="form-control" name="zwg_mpos" id="zwg_mpos" step="0.01">
              </div>
            </div>
            
            <div class="col-lg-2">
              <div class="form-group">
                <label class="form-label" for="usd_credit_sales">Credit Sales:</label>
                <input type="number" class="form-control" name="zwg_credit_sales" id="zwg_credit_sales" step="0.01" readonly>
              </div>
            </div>
            

            
     
      
        <div class="row">
        <div class="form-group col-lg-2">
          <label class="form-label" for="third_party_premiums">3rd Party Premiums:</label>
          <input type="number" class="form-control" name="zwg_third_party_premiums" id="zwg_third_party_premiums" step="0.01">
        </div>

        <div class="form-group col-lg-2">
          <label class="form-label" for="full_cover_premiums">Full Cover Premiums:</label>
          <input type="number" class="form-control" name="zwg_full_cover_premiums" id="zwg_full_cover_premiums" step="0.01">
        </div>

        <div class="form-group col-lg-2">
          <label class="form-label" for="zinara_fees">Zinara Fees:</label>
          <input type="number" class="form-control" name="zwg_zinara_fees" id="zwg_zinara_fees" step="0.01">
        </div>
         <div class="form-group col-lg-2">
          <label class="form-label" for="other_insurances_zwg">Other ZWG Insurances:</label>
          <input type="number" class="form-control" name="other_insurances_zwg" id="other_insurances_zwg" step="0.01" >
        </div>

        <div class="form-group col-lg-2">
          <label class="form-label" for="zwg_debit_sales">Debit Sales:</label>
          <input type="number" class="form-control" name="zwg_debit_sales" id="zwg_debit_sales" step="0.01" readonly>
        </div>
        <div class="col-lg-2">
        <label class="form-label" for="zwg_deposited_cash">Cash Deposited:</label>
        <input type="number" class="form-control" name="zwg_total_deposited" id="zwg_deposited_cash" step="0.01">
</div>
       
            </div>
          </div>
        </div>
      </div>

      <br>

      <div class="row">
        <div class="col-lg-4">
          <div class="form-group">
            <label class="form-label" for="bank">Bank:</label>
            <select class="form-control" name="bank" id="bank" required="required">
              <option value="">--Select Bank--</option>
              <!-- <option value="No Deposit">--No Deposit--</Select></option> -->
              <option value="POSB">POSB</option>
              <option value="CBZ">CBZ</option>
              <option value="Ecocash">Ecocash</option>
              <option value="FBC">FBC</option>
              <option value="BancAbc">BancAbc</option>
              <option value="NMB">NMB</option>
              <option value="Post Money">Post Money</option>
              <option value="Innbucks">Innbucks</option>
              <option value="Ecocash">Ecocash</option>
              <option value="CBZ GRUMA">CBZ GRUMA</option>
              <option value="CBZ Blue Streak">CBZ Blue Streak</option>
              <option value="Nedbank">Nedbank</option>
              <option value="HQ Cash">HQ Cash</option>
              <option value="ZB Bank">ZB Bank</option>
              <option value="Stanbic">Stanbic</option>
            </select>
          </div>
        </div>

        

      </div>

      <br>

      <div class="col-lg-12">
        <div class="form-group">
          <label class="form-label" for="comments">Comments:</label>
          <textarea class="form-control" name="comments" id="comments"></textarea>
        </div>
      </div>
<br><br>
     
<div class="form-group">
      <button id="submit-button" type="submit" class="btn btn-primary">Submit</button>
</div>
</span>
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
        const otherUSD = document.getElementById('other_insurances_usd');


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
        const otherZWG= document.getElementById('other_insurances_zwg');


    // Function to calculate USD sales
    function calculateUsdSales() {
        // Calculate b (cash + swipe + transfers)
        const b = (parseFloat(usdCashInput.value) || 0) + 
                 (parseFloat(usdSwipeInput.value) || 0) + 
                 (parseFloat(usdTransfersInput.value) || 0)
                 + (parseFloat(usdMPOS.value) || 0);
        
        // Calculate a (third party + full cover + zinara fees)
        const a = (parseFloat(usdThirdPartyPremiumsInput.value) || 0) + 
                 (parseFloat(usdFullCoverPremiumsInput.value) || 0) + 
                (parseFloat(usdZinaraFeesInput.value) || 0)+
                 (parseFloat(otherUSD.value) || 0);
        
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
                  (parseFloat(zwgZinaraFeesInput.value) || 0)+
                 (parseFloat(otherZWG.value) || 0);
        
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
        otherUSD.addEventListener('input', calculateUsdSales);

    usdThirdPartyPremiumsInput.addEventListener('input', calculateUsdSales);
    usdFullCoverPremiumsInput.addEventListener('input', calculateUsdSales);
    usdZinaraFeesInput.addEventListener('input', calculateUsdSales);

    // ZWG Event listeners
    zwgCashInput.addEventListener('input', calculateZwgSales);
    zwgSwipeInput.addEventListener('input', calculateZwgSales);
    zwgTransfersInput.addEventListener('input', calculateZwgSales);
    zwgMPOS.addEventListener('input', calculateZwgSales);
        otherZWG.addEventListener('input', calculateZwgSales);

    zwgThirdPartyPremiumsInput.addEventListener('input', calculateZwgSales);
    zwgFullCoverPremiumsInput.addEventListener('input', calculateZwgSales);
    zwgZinaraFeesInput.addEventListener('input', calculateZwgSales);
});

const submitButton = document.getElementById('submit-button');

/**
 * Checks if the current time falls within the 16-hour open period 
 * (from 4:00 PM of the current day until 8:00 AM of the following day).
 */

function checkTime() {
            const now = new Date();
            const currentHour = now.getHours();
            const currentMinute = now.getMinutes();
            const currentDay = now.getDay(); // 0 = Sunday, 6 = Saturday

            // --- MODIFIED LOGIC START ---

            // We set the default open hour to 4:00 PM (16:00) for weekdays (Mon-Fri).
            let openHour = 16; 
            const closeHour = 9; // Closing time remains 8:00 AM (next day)

            // Modification: Set opening hour to 11:00 AM for Saturday (6) and Sunday (0)
            if (currentDay === 0 || currentDay === 6) {
                openHour = 11; // 11:00 AM
            }

            // --- MODIFIED LOGIC END ---

            let isOpen = false;

            if (currentHour >= openHour) {
                // Case 1: Time is between openHour (e.g., 11:00 or 16:00) and midnight (same day)
                isOpen = true;
            } else if (currentHour < closeHour) {
                // Case 2: Time is between midnight (00:00) and closeHour (8:00 AM next day)
                isOpen = true;
            } else {
                // Case 3: Closed period (between closeHour and openHour)
                isOpen = false;
            }

            // Button should be hidden exactly at closing time (8:00 AM sharp)
            if (currentHour === closeHour && currentMinute === 0) {
                isOpen = false;
            }

            // UI Update Logic
            const submitButton = document.getElementById('submitButton');
            const closedMessage = document.getElementById('closedMessage');
            const currentTimeDisplay = document.getElementById('currentTimeDisplay');

            const options = { weekday: 'long', hour: 'numeric', minute: 'numeric', second: 'numeric', hour12: true };
            currentTimeDisplay.textContent = now.toLocaleTimeString('en-US', options);
            
            if (submitButton) {
                submitButton.style.display = isOpen ? 'block' : 'none';
                closedMessage.style.display = isOpen ? 'none' : 'block';
            }
        }

        // Run the checker immediately and then update every minute
        window.onload = function() {
            checkTime();
            // Update every 30 seconds to keep the time display current
            setInterval(checkTime, 30000); 
        };


// Check the time immediately on page 
</script>


<script>
document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById("collectionForm");
    const submitBtn = document.getElementById("submit-button");

    // ✅ Prevent submitting with Enter key
    form.addEventListener("keydown", function(event) {
        if (event.key === "Enter") {
            event.preventDefault();
            return false;
        }
    });

    // ✅ Disable button after first click to prevent double submit
    form.addEventListener("submit", function(event) {
        submitBtn.disabled = true;
        submitBtn.textContent = "Submitting...";
    });
});
</script>



<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/macbookair/Documents/Projects/gruma-5/resources/views/collection/create.blade.php ENDPATH**/ ?>