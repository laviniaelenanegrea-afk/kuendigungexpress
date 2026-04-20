(function () {
  "use strict";

  function toggleDateField() {
    var modeEl = document.getElementById("terminationMode");
    var box = document.getElementById("dateBox");
    var dateInput = document.getElementById("terminationDate");
    if (!modeEl || !box || !dateInput) return;

    if (modeEl.value === "specific_date") {
      box.style.display = "block";
      dateInput.required = true;
    } else {
      box.style.display = "none";
      dateInput.required = false;
      dateInput.value = "";
    }
  }

  function toggleProviderEmailRequirement() {
    var cb = document.getElementById("sendEmail");
    var provider = document.getElementById("providerEmail");
    var providerWrap = document.getElementById("providerEmailWrap");
    if (!cb || !provider || !providerWrap) return;

    if (cb.checked) {
      provider.required = true;
      providerWrap.style.display = "block";
    } else {
      provider.required = false;
      provider.value = "";
      providerWrap.style.display = "none";
    }
  }

  function onReady(fn) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", fn);
    } else {
      fn();
    }
  }

  onReady(function () {
    var modeEl = document.getElementById("terminationMode");
    var sendEl = document.getElementById("sendEmail");

    if (modeEl) modeEl.addEventListener("change", toggleDateField);
    if (sendEl) sendEl.addEventListener("change", toggleProviderEmailRequirement);

    // initial state
    toggleDateField();
    toggleProviderEmailRequirement();
  });
})();
