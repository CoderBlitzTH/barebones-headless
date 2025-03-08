jQuery(document).ready(function ($) {
  const messages = {
    noSelection: "กรุณาเลือกโพสต์อย่างน้อย 1 รายการ",
    connectionError: "เกิดข้อผิดพลาดในการเชื่อมต่อ",
  };

  function handleAjaxResponse(response) {
    alert(response.success ? response.data.message : "เกิดข้อผิดพลาด: " + response.data.message);
  }

  function handleAjaxError(xhr, status, error) {
    console.error("AJAX Error:", status, error);
    alert(messages.connectionError);
  }

  // Revalidate เดี่ยว
  $(".bbh-revalidate").on("click", function (e) {
    e.preventDefault();
    const $this = $(this);
    $.ajax({
      url: bbhRevalidate.ajax_url,
      method: "POST",
      data: {
        action: "bbh_manual_revalidate",
        id: $this.data("id"),
        type: $this.data("type"),
        taxonomy: $this.data("taxonomy") || "",
        nonce: $this.data("nonce"),
      },
      success: handleAjaxResponse,
      error: handleAjaxError,
    });
  });

  // Bulk Action
  $("#doaction, #doaction2").on("click", function (e) {
    const $form = $(this).closest("form");
    const action = $form.find('select[name="action"], select[name="action2"]').val();

    if (action !== "bbh_revalidate") return;

    e.preventDefault();

    const selected = $('.check-column input[type="checkbox"]:checked')
      .map(function () {
        return $(this).val();
      })
      .get();

    if (!selected.length) {
      alert(messages.noSelection);
      return;
    }

    $.ajax({
      url: bbhRevalidate.ajax_url,
      method: "POST",
      data: {
        action: "bbh_manual_revalidate",
        post_ids: selected,
        nonce: bbhRevalidate.nonce,
      },
      success: handleAjaxResponse,
      error: handleAjaxError,
    });
  });
});
