"use strict";
var InvoiceApp = (() => {
  var __defProp = Object.defineProperty;
  var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
  var __getOwnPropNames = Object.getOwnPropertyNames;
  var __hasOwnProp = Object.prototype.hasOwnProperty;
  var __export = (target, all) => {
    for (var name in all)
      __defProp(target, name, { get: all[name], enumerable: true });
  };
  var __copyProps = (to, from, except, desc) => {
    if (from && typeof from === "object" || typeof from === "function") {
      for (let key of __getOwnPropNames(from))
        if (!__hasOwnProp.call(to, key) && key !== except)
          __defProp(to, key, { get: () => from[key], enumerable: !(desc = __getOwnPropDesc(from, key)) || desc.enumerable });
    }
    return to;
  };
  var __toCommonJS = (mod) => __copyProps(__defProp({}, "__esModule", { value: true }), mod);

  // src/typescript/index.ts
  var index_exports = {};
  __export(index_exports, {
    InvoiceApp: () => InvoiceApp
  });

  // src/typescript/utils.ts
  function parsedata(data) {
    if (!data) return {};
    if (typeof data === "object" && data !== null) return data;
    if (typeof data === "string") {
      try {
        return JSON.parse(data);
      } catch (e) {
        return {};
      }
    }
    return {};
  }
  async function getJson(url, params, options = {}) {
    let requestUrl = url;
    if (params) {
      const searchParams = new URLSearchParams();
      Object.entries(params).forEach(([key, value]) => {
        if (Array.isArray(value)) {
          value.forEach((item) => {
            if (item !== null && item !== void 0) {
              searchParams.append(`${key}[]`, String(item));
            }
          });
        } else if (value !== void 0 && value !== null) {
          searchParams.append(key, String(value));
        }
      });
      const separator = url.includes("?") ? "&" : "?";
      requestUrl = `${url}${separator}${searchParams.toString()}`;
    }
    const defaultOptions = {
      method: "GET",
      credentials: "same-origin",
      cache: "no-store",
      headers: { Accept: "application/json" },
      ...options
    };
    const response = await fetch(requestUrl, defaultOptions);
    if (!response.ok) {
      throw new Error(`Network response not ok: ${response.status}`);
    }
    const text = await response.text();
    try {
      return JSON.parse(text);
    } catch (e) {
      return text;
    }
  }
  function closestSafe(element, selector) {
    try {
      if (!element) return null;
      if (typeof element.closest === "function") {
        return element.closest(selector);
      }
      let node = element;
      while (node) {
        if (node.matches && node.matches(selector)) {
          return node;
        }
        node = node.parentElement;
      }
    } catch (e) {
      console.warn("closestSafe error:", e);
      return null;
    }
    return null;
  }
  function getElementById(id) {
    return document.getElementById(id);
  }
  function querySelector(selector) {
    return document.querySelector(selector);
  }
  function getInputValue(id) {
    const element = getElementById(id);
    return element?.value || "";
  }

  // src/typescript/create-credit.ts
  var CreateCreditHandler = class {
    confirmButtonSelector = ".create-credit-confirm";
    constructor() {
      this.initialize();
    }
    initialize() {
      document.addEventListener("click", this.handleClick.bind(this), true);
    }
    async handleClick(event) {
      const target = event.target;
      if (!target || target.id !== "create-credit-confirm") {
        return;
      }
      event.preventDefault();
      try {
        await this.processCreateCredit();
      } catch (error) {
        console.error("Create credit error:", error);
        alert(`Error: ${error instanceof Error ? error.message : "Unknown error"}`);
      }
    }
    async processCreateCredit() {
      const url = `${location.origin}/invoice/inv/create_credit_confirm`;
      const btn = querySelector(this.confirmButtonSelector);
      const absoluteUrl = new URL(location.href);
      if (btn) {
        btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
      }
      const invId = absoluteUrl.pathname.split("/").at(-1) || "";
      const formData = {
        inv_id: invId,
        client_id: getInputValue("client_id"),
        inv_date_created: getInputValue("inv_date_created"),
        group_id: getInputValue("inv_group_id"),
        password: getInputValue("inv_password"),
        user_id: getInputValue("user_id")
      };
      const data = await getJson(url, formData);
      const response = parsedata(data);
      if (response.success === 1) {
        if (btn) {
          btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check2-square"></i></h2>';
        }
        if (response.flash_message) {
          alert(response.flash_message);
        }
        location.href = absoluteUrl.href;
        location.reload();
      } else {
        if (btn) {
          btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
        }
        if (response.flash_message) {
          alert(response.flash_message);
        }
        location.href = absoluteUrl.href;
        location.reload();
      }
    }
  };

  // src/typescript/quote.ts
  function secureReload() {
    window.location.reload();
  }
  function secureInsertHTML(element, html) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, "text/html");
    const fragment = document.createDocumentFragment();
    Array.from(doc.body.children).forEach((child) => fragment.appendChild(child));
    element.innerHTML = "";
    element.appendChild(fragment);
  }
  function getQuoteIdFromUrl() {
    const url = new URL(location.href);
    return url.pathname.split("/").at(-1) || "";
  }
  function getFieldValue(id) {
    const element = document.getElementById(id);
    return element?.value || "";
  }
  function setButtonLoading(button, isLoading, originalHtml) {
    if (isLoading) {
      button.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
      button.disabled = true;
    } else {
      button.innerHTML = originalHtml || '<h6 class="text-center"><i class="fa fa-check"></i></h6>';
      button.disabled = false;
    }
  }
  var QuoteHandler = class {
    constructor() {
      this.bindEventListeners();
      this.initializeComponents();
    }
    bindEventListeners() {
      document.addEventListener("click", this.handleClick.bind(this), true);
      document.addEventListener("input", this.handleInput.bind(this), true);
      document.addEventListener("focus", this.handleFocus.bind(this), true);
      document.addEventListener("click", this.handleClientNoteSave.bind(this), true);
      document.addEventListener("click", this.handleQuoteTaxSubmit.bind(this), true);
    }
    handleClick(event) {
      const target = event.target;
      const deleteBtn = target.closest(".btn_delete_item");
      if (deleteBtn) {
        this.handleDeleteItem(deleteBtn);
        return;
      }
      const delMulti = target.closest(".delete-items-confirm-quote");
      if (delMulti) {
        this.handleDeleteMultipleItems(delMulti);
        return;
      }
      const addRowModalBtn = target.closest(".btn_add_row_modal");
      if (addRowModalBtn) {
        this.handleAddRowModal();
        return;
      }
      const btnQuoteItemAddRow = target.closest(".btn_quote_item_add_row");
      if (btnQuoteItemAddRow) {
        this.handleAddQuoteItemRow();
        return;
      }
      const addRowBtn = target.closest(".btn_add_row");
      if (addRowBtn) {
        this.handleAddGenericRow();
        return;
      }
      const addClientBtn = target.closest(".quote_add_client");
      if (addClientBtn) {
        this.handleAddClientModal();
        return;
      }
      const createConfirm = target.closest(
        "#quote_create_confirm, .quote_create_confirm"
      );
      if (createConfirm) {
        this.handleQuoteCreateConfirm();
        return;
      }
      const poConfirm = target.closest(
        "#quote_with_purchase_order_number_confirm, .quote_with_purchase_order_number_confirm"
      );
      if (poConfirm) {
        this.handleQuotePurchaseOrderConfirm(poConfirm);
        return;
      }
      const toInvoice = target.closest(
        "#quote_to_invoice_confirm, .quote_to_invoice_confirm"
      );
      if (toInvoice) {
        this.handleQuoteToInvoiceConfirm(toInvoice);
        return;
      }
      const toSo = target.closest("#quote_to_so_confirm, .quote_to_so_confirm");
      if (toSo) {
        this.handleQuoteToSalesOrderConfirm(toSo);
        return;
      }
      const toQuote = target.closest(
        "#quote_to_quote_confirm, .quote_to_quote_confirm"
      );
      if (toQuote) {
        this.handleQuoteToQuoteConfirm(toQuote);
        return;
      }
      this.handlePdfGeneration(target);
    }
    async handleDeleteItem(deleteBtn) {
      const id = deleteBtn.getAttribute("data-id");
      if (!id) {
        const parentItem = deleteBtn.closest(".item");
        parentItem?.remove();
        return;
      }
      try {
        const url = `${location.origin}/invoice/quote/delete_item/${encodeURIComponent(id)}`;
        const response = await getJson(url, { id });
        const data = parsedata(response);
        if (data.success === 1) {
          location.reload();
          const parentItem = deleteBtn.closest(".item");
          parentItem?.remove();
          alert("Deleted");
        } else {
          console.warn("delete_item failed", data);
        }
      } catch (error) {
        console.error("delete_item error", error);
        alert("An error occurred while deleting item. See console for details.");
      }
    }
    async handleDeleteMultipleItems(delMulti) {
      const originalHtml = delMulti.innerHTML;
      setButtonLoading(delMulti, true);
      try {
        const itemCheckboxes = document.querySelectorAll(
          "input[name='item_ids[]']:checked"
        );
        const item_ids = Array.from(itemCheckboxes).map((input) => parseInt(input.value, 10)).filter(Boolean).toSorted((a, b) => a - b);
        const response = await getJson("/invoice/quoteitem/multiple", {
          item_ids
        });
        const data = parsedata(response);
        if (data.success === 1) {
          delMulti.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
          location.reload();
        } else {
          console.warn("quoteitem/multiple failed", data);
          setButtonLoading(delMulti, false, originalHtml);
        }
      } catch (error) {
        console.error("quoteitem/multiple error", error);
        setButtonLoading(delMulti, false, originalHtml);
        alert("An error occurred while deleting items. See console for details.");
      }
    }
    async handleAddRowModal() {
      const quoteId = getQuoteIdFromUrl();
      const url = `${location.origin}/invoice/quoteitem/add/${encodeURIComponent(quoteId)}`;
      const placeholder = document.getElementById("modal-placeholder-quoteitem");
      if (!placeholder) return;
      try {
        placeholder.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
        const response = await fetch(url, { cache: "no-store", credentials: "same-origin" });
        const html = await response.text();
        secureInsertHTML(placeholder, html);
      } catch (error) {
        console.error("Failed to load quoteitem modal", error);
      }
    }
    handleAddQuoteItemRow() {
      const template = document.getElementById("new_quote_item_row");
      const table = document.getElementById("item_table");
      if (template && table) {
        const clone = template.cloneNode(true);
        clone.removeAttribute("id");
        clone.classList.add("item");
        clone.style.display = "";
        table.appendChild(clone);
      }
    }
    handleAddGenericRow() {
      const template = document.getElementById("new_row");
      const table = document.getElementById("item_table");
      if (template && table) {
        const clone = template.cloneNode(true);
        clone.removeAttribute("id");
        clone.classList.add("item");
        clone.style.display = "";
        table.appendChild(clone);
      }
    }
    async handleAddClientModal() {
      const url = `${location.origin}/invoice/add-a-client`;
      const placeholder = document.getElementById("modal-placeholder-client");
      if (!placeholder) return;
      try {
        placeholder.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
        const response = await fetch(url, { cache: "no-store", credentials: "same-origin" });
        const html = await response.text();
        secureInsertHTML(placeholder, html);
      } catch (error) {
        console.error("Failed to load add-a-client modal", error);
      }
    }
    async handleQuoteCreateConfirm() {
      const url = `${location.origin}/invoice/quote/create_confirm`;
      const btn = document.querySelector(".quote_create_confirm");
      const originalHtml = btn?.innerHTML || "";
      if (btn) {
        setButtonLoading(btn, true);
      }
      try {
        const payload = {
          client_id: getFieldValue("create_quote_client_id"),
          quote_group_id: getFieldValue("quote_group_id"),
          quote_password: getFieldValue("quote_password")
        };
        const response = await getJson(url, payload);
        const data = parsedata(response);
        if (data.success === 1) {
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
          secureReload();
        } else if (data.success === 0) {
          if (btn) btn.innerHTML = '<h6 class="text-center"><i class="fa fa-check"></i></h6>';
          secureReload();
          if (data.message) alert(data.message);
        }
      } catch (error) {
        console.error("create_confirm error", error);
        if (btn) {
          setButtonLoading(btn, false, originalHtml);
        }
        alert("An error occurred while creating quote. See console for details.");
      }
    }
    async handleQuotePurchaseOrderConfirm(poConfirm) {
      const url = `${location.origin}/invoice/quote/approve`;
      const btn = document.querySelector(".quote_with_purchase_order_number_confirm") || poConfirm;
      if (btn) {
        setButtonLoading(btn, true);
      }
      try {
        const payload = {
          url_key: getFieldValue("url_key"),
          client_po_number: getFieldValue("quote_with_purchase_order_number"),
          client_po_person: getFieldValue("quote_with_purchase_order_person")
        };
        const response = await getJson(url, payload);
        const data = parsedata(response);
        if (data.success === 1) {
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
          secureReload();
        }
      } catch (error) {
        console.error("approve error", error);
        if (btn) {
          setButtonLoading(btn, false);
        }
        alert("An error occurred while approving quote. See console for details.");
      }
    }
    async handleQuoteToInvoiceConfirm(toInvoice) {
      const url = `${location.origin}/invoice/quote/quote_to_invoice_confirm`;
      const btn = document.querySelector(".quote_to_invoice_confirm") || toInvoice;
      const originalHtml = btn?.innerHTML || "";
      if (btn) {
        setButtonLoading(btn, true);
      }
      try {
        const quoteId = getQuoteIdFromUrl();
        const payload = {
          quote_id: quoteId,
          client_id: getFieldValue("client_id"),
          group_id: getFieldValue("group_id"),
          password: getFieldValue("password")
        };
        const response = await getJson(url, payload);
        const data = parsedata(response);
        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
        if (data.success && data.redirect_url) {
          window.location.href = data.redirect_url;
        } else if (data.success && data.new_invoice_id) {
          window.location.href = `${location.origin}/invoice/inv/view/${data.new_invoice_id}`;
        } else {
          secureReload();
        }
        if (data.flash_message) alert(data.flash_message);
      } catch (error) {
        console.error("quote_to_invoice_confirm error", error);
        if (btn) {
          setButtonLoading(btn, false, originalHtml);
        }
        alert("An error occurred while converting quote to invoice. See console for details.");
      }
    }
    async handleQuoteToSalesOrderConfirm(toSo) {
      const url = `${location.origin}/invoice/quote/quote_to_so_confirm`;
      const btn = document.querySelector(".quote_to_so_confirm") || toSo;
      const originalHtml = btn?.innerHTML || "";
      if (btn) {
        setButtonLoading(btn, true);
      }
      try {
        const quoteId = getQuoteIdFromUrl();
        const payload = {
          quote_id: quoteId,
          client_id: getFieldValue("client_id"),
          group_id: getFieldValue("so_group_id"),
          po_number: getFieldValue("po_number"),
          po_person: getFieldValue("po_person"),
          password: getFieldValue("password")
        };
        const response = await getJson(url, payload);
        const data = parsedata(response);
        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
        if (data.success && data.redirect_url) {
          window.location.href = data.redirect_url;
        } else {
          secureReload();
        }
        if (data.flash_message) alert(data.flash_message);
      } catch (error) {
        console.error("quote_to_so_confirm error", error);
        if (btn) {
          setButtonLoading(btn, false, originalHtml);
        }
        alert("An error occurred while converting quote to SO. See console for details.");
      }
    }
    async handleQuoteToQuoteConfirm(toQuote) {
      const url = `${location.origin}/invoice/quote/quote_to_quote_confirm`;
      const btn = document.querySelector(".quote_to_quote_confirm") || toQuote;
      const originalHtml = btn?.innerHTML || "";
      if (btn) {
        setButtonLoading(btn, true);
      }
      try {
        const quoteId = getQuoteIdFromUrl();
        const payload = {
          quote_id: quoteId,
          client_id: getFieldValue("create_quote_client_id"),
          user_id: getFieldValue("user_id")
        };
        const response = await getJson(url, payload);
        const data = parsedata(response);
        if (data.success === 1) {
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
          secureReload();
          if (data.flash_message) alert(data.flash_message);
        }
      } catch (error) {
        console.error("quote_to_quote_confirm error", error);
        if (btn) {
          setButtonLoading(btn, false, originalHtml);
        }
        alert("An error occurred while copying quote. See console for details.");
      }
    }
    handlePdfGeneration(target) {
      if (target.closest("#quote_to_pdf_confirm_with_custom_fields")) {
        const url = `${location.origin}/invoice/quote/pdf/1`;
        window.open(url, "_blank");
        return;
      }
      if (target.closest("#quote_to_pdf_confirm_without_custom_fields")) {
        const url = `${location.origin}/invoice/quote/pdf/0`;
        window.open(url, "_blank");
        return;
      }
    }
    async handleClientNoteSave(event) {
      const target = event.target;
      const saveBtn = target.closest("#save_client_note");
      if (!saveBtn) return;
      const url = `${location.origin}/invoice/client/save_client_note`;
      const loadUrl = `${location.origin}/invoice/client/load_client_notes`;
      try {
        const payload = {
          client_id: getFieldValue("client_id"),
          client_note: getFieldValue("client_note")
        };
        const response = await getJson(url, payload);
        const data = parsedata(response);
        if (data.success === 1) {
          document.querySelectorAll(".control-group").forEach((group) => {
            group.classList.remove("error");
          });
          const noteEl = document.getElementById("client_note");
          if (noteEl) noteEl.value = "";
          const notesList = document.getElementById("notes_list");
          if (notesList) {
            const loadUrlWithParams = `${loadUrl}?client_id=${encodeURIComponent(payload.client_id)}`;
            const notesResponse = await fetch(loadUrlWithParams, {
              cache: "no-store",
              credentials: "same-origin"
            });
            const html = await notesResponse.text();
            secureInsertHTML(notesList, html);
          }
        } else {
          document.querySelectorAll(".control-group").forEach((group) => {
            group.classList.remove("error");
          });
          if (data.validation_errors) {
            Object.entries(data.validation_errors).forEach(([key, errors]) => {
              const elm = document.getElementById(key);
              if (elm?.parentElement) {
                elm.parentElement.classList.add("has-error");
              }
            });
          }
        }
      } catch (error) {
        console.error("save_client_note error", error);
        alert("Status: error An error occurred");
      }
    }
    async handleQuoteTaxSubmit(event) {
      const target = event.target;
      const submit = target.closest("#quote_tax_submit");
      if (!submit) return;
      const url = `${location.origin}/invoice/quote/save_quote_tax_rate`;
      const btn = document.querySelector(".quote_tax_submit") || submit;
      if (btn) {
        setButtonLoading(btn, true);
      }
      try {
        const quoteId = getQuoteIdFromUrl();
        const payload = {
          quote_id: quoteId,
          tax_rate_id: getFieldValue("tax_rate_id"),
          include_item_tax: getFieldValue("include_item_tax")
        };
        const response = await getJson(url, payload);
        const data = parsedata(response);
        secureReload();
        if (data.flash_message) alert(data.flash_message);
      } catch (error) {
        console.error("save_quote_tax_rate error", error);
        alert("An error occurred while saving quote tax rate. See console for details.");
      }
    }
    handleInput(event) {
      const target = event.target;
      if (target.id === "quote_discount_amount") {
        const percentEl = document.getElementById("quote_discount_percent");
        if (target.value.length > 0) {
          if (percentEl) {
            percentEl.value = "0.00";
            percentEl.disabled = true;
          }
        } else {
          if (percentEl) percentEl.disabled = false;
        }
      }
      if (target.id === "quote_discount_percent") {
        const amountEl = document.getElementById("quote_discount_amount");
        if (target.value.length > 0) {
          if (amountEl) {
            amountEl.value = "0.00";
            amountEl.disabled = true;
          }
        } else {
          if (amountEl) amountEl.disabled = false;
        }
      }
    }
    handleFocus(event) {
      const target = event.target;
      if (target.id === "datepicker") {
        this.initializeDatepicker(target);
      }
      if (target.classList?.contains("datepicker")) {
        this.initializeDatepicker(target);
      }
      if (target.classList?.contains("taggable")) {
        window.lastTaggableClicked = target;
      }
    }
    initializeDatepicker(element) {
      if (window.jQuery?.fn?.datepicker) {
        if (element.id === "datepicker") {
          window.jQuery(element).datepicker({
            changeMonth: true,
            changeYear: true,
            showButtonPanel: true,
            dateFormat: "dd-mm-yy"
          });
        } else {
          window.jQuery(element).datepicker({
            beforeShow: () => {
              setTimeout(() => {
                document.querySelectorAll(".datepicker").forEach((d) => {
                  d.style.zIndex = "9999";
                });
              }, 0);
            }
          });
        }
      }
    }
    initializeComponents() {
      if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", () => {
          this.initializeTooltips();
          this.initializeTagSelect();
        });
      } else {
        this.initializeTooltips();
        this.initializeTagSelect();
      }
    }
    initializeTooltips() {
      if (typeof window.bootstrap?.Tooltip !== "undefined") {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
          try {
            new window.bootstrap.Tooltip(element);
          } catch (error) {
          }
        });
      }
    }
    initializeTagSelect() {
      document.querySelectorAll(".tag-select").forEach((select) => {
        const selectElement = select;
        selectElement.addEventListener("change", (event) => {
          const currentTarget = event.currentTarget;
          if (window.lastTaggableClicked) {
            this.insertAtCaret(window.lastTaggableClicked.id, currentTarget.value);
          }
          if (currentTarget._tomselect?.clear) {
            currentTarget._tomselect.clear();
          } else if (currentTarget.tomselect?.clear) {
            currentTarget.tomselect.clear();
          } else if (currentTarget.multiple) {
            Array.from(currentTarget.options).toReversed().forEach((option) => {
              option.selected = false;
            });
          } else {
            currentTarget.value = "";
          }
          event.preventDefault();
          return false;
        });
      });
    }
    insertAtCaret(elementId, text) {
      const element = document.getElementById(elementId);
      if (!element) return;
      const startPos = element.selectionStart || 0;
      const endPos = element.selectionEnd || 0;
      const value = element.value;
      element.value = value.substring(0, startPos) + text + value.substring(endPos);
      element.setSelectionRange(startPos + text.length, startPos + text.length);
      element.focus();
    }
  };

  // src/typescript/client.ts
  function getFieldValue2(id) {
    const element = document.getElementById(id);
    return element?.value || "";
  }
  function secureReload2() {
    window.location.reload();
  }
  function createSecureUIElement(type = "h6", className = "text-center", iconClass = "fa fa-spin fa-spinner") {
    const element = document.createElement(type);
    element.className = className;
    const icon = document.createElement("i");
    icon.className = iconClass;
    element.appendChild(icon);
    return element;
  }
  function setButtonLoading2(button, isLoading, originalHtml) {
    if (isLoading) {
      button.textContent = "";
      button.appendChild(createSecureUIElement("h6", "text-center", "fa fa-spin fa-spinner"));
      button.disabled = true;
    } else {
      if (originalHtml) {
        button.textContent = "";
        button.appendChild(createSecureUIElement("h6", "text-center", "fa fa-check"));
      } else {
        button.textContent = "";
        button.appendChild(createSecureUIElement("h6", "text-center", "fa fa-check"));
      }
      button.disabled = false;
    }
  }
  function setSecureButtonContent(btn, type = "h6", className = "text-center", iconClass = "fa fa-spin fa-spinner") {
    if (!btn) return;
    btn.textContent = "";
    btn.appendChild(createSecureUIElement(type, className, iconClass));
  }
  var ClientHandler = class {
    constructor() {
      this.bindEventListeners();
    }
    bindEventListeners() {
      document.addEventListener("click", this.handleClick.bind(this), true);
      this.bindSpecificFormHandlers();
    }
    bindSpecificFormHandlers() {
      const bindToForm = (formId, handler) => {
        const form = document.getElementById(formId);
        if (form) {
          form.addEventListener("submit", handler.bind(this));
        } else {
          if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", () => {
              const laterForm = document.getElementById(formId);
              if (laterForm) {
                laterForm.addEventListener("submit", handler.bind(this));
              }
            });
          }
        }
      };
      bindToForm("QuoteForm", this.handleQuoteFormSubmit);
      bindToForm("InvForm", this.handleInvoiceFormSubmit);
    }
    handleClick(event) {
      const target = event.target;
      const createBtn = target.closest("#client_create_confirm");
      if (createBtn) {
        this.handleClientCreateConfirm(createBtn);
        return;
      }
      const saveNoteBtn = target.closest("#save_client_note_new");
      if (saveNoteBtn) {
        this.handleSaveClientNote(saveNoteBtn);
        return;
      }
      const deleteNoteBtn = target.closest(".client-note-delete-btn");
      if (deleteNoteBtn) {
        this.handleDeleteClientNote(deleteNoteBtn);
        return;
      }
    }
    async handleClientCreateConfirm(createBtn) {
      const url = `${location.origin}/invoice/client/create_confirm`;
      const btn = document.querySelector(".client_create_confirm") || createBtn;
      if (btn) {
        setButtonLoading2(btn, true);
      }
      try {
        const payload = {
          client_name: getFieldValue2("client_name"),
          client_surname: getFieldValue2("client_surname"),
          client_email: getFieldValue2("client_email")
        };
        const response = await getJson(url, payload);
        const data = parsedata(response);
        if (data.success === 1) {
          if (btn) {
            setSecureButtonContent(btn, "h2", "text-center", "fa fa-check");
          }
          secureReload2();
        } else {
          if (btn) {
            setButtonLoading2(btn, false);
          }
          console.warn("create_confirm response", data);
        }
      } catch (error) {
        console.warn(error);
        if (btn) {
          setButtonLoading2(btn, false);
        }
        alert("An error occurred while creating client. See console for details.");
      }
    }
    async handleSaveClientNote(saveNoteBtn) {
      const url = `${location.origin}/invoice/client/save_client_note_new`;
      const loadNotesUrl = `${location.origin}/invoice/client/load_client_notes`;
      const btn = document.querySelector(".save_client_note") || saveNoteBtn;
      const currentUrl = new URL(location.href);
      if (btn) {
        setButtonLoading2(btn, true);
      }
      try {
        const payload = {
          client_id: getFieldValue2("client_id"),
          client_note: getFieldValue2("client_note")
        };
        const response = await getJson(url, payload);
        const data = parsedata(response);
        if (data.success === 1) {
          if (btn) {
            setSecureButtonContent(btn, "h2", "text-center", "fa fa-check");
          }
          const noteEl = document.getElementById("client_note");
          if (noteEl) noteEl.value = "";
          const notesList = document.getElementById("notes_list");
          if (notesList) {
            const loadUrl = `${loadNotesUrl}?client_id=${encodeURIComponent(payload.client_id)}`;
            try {
              const notesResponse = await fetch(loadUrl, {
                cache: "no-store",
                credentials: "same-origin"
              });
              const html = await notesResponse.text();
              if (html && !html.includes("<!DOCTYPE") && !html.includes("<html")) {
                notesList.textContent = "";
                const parser = new DOMParser();
                try {
                  const doc = parser.parseFromString(html, "text/html");
                  if (doc && doc.body) {
                    const fragment = document.createDocumentFragment();
                    while (doc.body.firstChild) {
                      fragment.appendChild(doc.body.firstChild);
                    }
                    notesList.appendChild(fragment);
                  }
                } catch (e) {
                  console.error("HTML parsing error:", e);
                  notesList.textContent = "Error loading notes";
                }
              } else {
                console.warn("Received full page HTML instead of notes fragment, reloading page");
                window.location.reload();
                return;
              }
            } catch (loadError) {
              console.error("load_client_notes failed", loadError);
              window.location.reload();
              return;
            }
          }
          setTimeout(() => {
            if (btn) {
              setButtonLoading2(btn, false, '<h6 class="text-center"><i class="fa fa-save"></i></h6>');
            }
          }, 1e3);
        } else {
          this.clearValidationErrors();
          if (data.validation_errors) {
            this.showValidationErrors(data.validation_errors);
          }
          if (btn) {
            setButtonLoading2(btn, false);
          }
        }
      } catch (error) {
        console.warn(error);
        if (btn) {
          setButtonLoading2(btn, false);
        }
        alert("An error occurred while saving client note. See console for details.");
      }
    }
    async handleDeleteClientNote(deleteBtn) {
      const noteId = deleteBtn.getAttribute("data-note-id");
      if (!noteId) {
        console.error("No note ID found on delete button");
        return;
      }
      if (!confirm("Are you sure you want to delete this note?")) {
        return;
      }
      const url = `${location.origin}/invoice/client/delete_client_note`;
      const originalHtml = deleteBtn.innerHTML;
      try {
        deleteBtn.textContent = "";
        const spinner = document.createElement("i");
        spinner.className = "fa fa-spin fa-spinner";
        deleteBtn.appendChild(spinner);
        deleteBtn.disabled = true;
        const response = await fetch(`${url}?note_id=${encodeURIComponent(noteId)}`, {
          method: "GET",
          credentials: "same-origin"
        });
        if (response.ok) {
          const data = await response.json();
          if (data.success === 1) {
            const notePanel = deleteBtn.closest(".panel");
            if (notePanel) {
              notePanel.remove();
            }
          } else {
            deleteBtn.innerHTML = originalHtml;
            deleteBtn.disabled = false;
            alert(data.message || "Failed to delete note. Please try again.");
          }
        } else {
          const responseText = await response.text();
          console.error("Delete client note HTTP error:", {
            status: response.status,
            statusText: response.statusText,
            body: responseText.substring(0, 500)
          });
          deleteBtn.innerHTML = originalHtml;
          deleteBtn.disabled = false;
          alert("Failed to delete note. Please try again.");
        }
      } catch (error) {
        console.error("Delete client note error:", error);
        deleteBtn.innerHTML = originalHtml;
        deleteBtn.disabled = false;
        alert("An error occurred while deleting the note. Please try again.");
      }
    }
    clearValidationErrors() {
      document.querySelectorAll(".control-group").forEach((group) => {
        group.classList.remove("error");
      });
    }
    showValidationErrors(validationErrors) {
      Object.entries(validationErrors).forEach(([key, errors]) => {
        const element = document.getElementById(key);
        if (element?.parentElement) {
          element.parentElement.classList.add("has-error");
        }
      });
    }
    async handleQuoteFormSubmit(event) {
      event.preventDefault();
      const form = event.target;
      const submitButton = form.querySelector('button[type="submit"]');
      const originalHtml = submitButton?.innerHTML;
      if (submitButton) {
        setButtonLoading2(submitButton, true);
      }
      if (submitButton) {
        submitButton.textContent = "";
        const checkIcon = document.createElement("i");
        checkIcon.className = "fa fa-check";
        submitButton.appendChild(checkIcon);
      }
      const modal = document.getElementById("modal-add-quote") || document.getElementById("modal-add-client");
      if (modal) {
        const bootstrapModal = window.bootstrap?.Modal?.getInstance(modal);
        if (bootstrapModal) {
          bootstrapModal.hide();
        }
      }
      setTimeout(() => {
        form.submit();
      }, 300);
    }
    async handleInvoiceFormSubmit(event) {
      event.preventDefault();
      const form = event.target;
      const submitButton = form.querySelector('button[type="submit"]');
      const originalHtml = submitButton?.innerHTML;
      if (submitButton) {
        setButtonLoading2(submitButton, true);
      }
      if (submitButton) {
        submitButton.textContent = "";
        const checkIcon = document.createElement("i");
        checkIcon.className = "fa fa-check";
        submitButton.appendChild(checkIcon);
      }
      const modal = document.getElementById("modal-add-inv") || document.getElementById("modal-add-client");
      if (modal) {
        const bootstrapModal = window.bootstrap?.Modal?.getInstance(modal);
        if (bootstrapModal) {
          bootstrapModal.hide();
        }
      }
      setTimeout(() => {
        form.submit();
      }, 300);
    }
  };

  // src/typescript/invoice.ts
  function setButtonLoading3(button, isLoading, originalHtml) {
    if (isLoading) {
      button.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
      button.disabled = true;
    } else {
      button.innerHTML = originalHtml || '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
      button.disabled = false;
    }
  }
  function getFieldValue3(id) {
    const element = document.getElementById(id);
    return element?.value || "";
  }
  var InvoiceHandler = class {
    constructor() {
      this.bindEventListeners();
    }
    bindEventListeners() {
      document.addEventListener("click", this.handleClick.bind(this), true);
      document.addEventListener("change", this.handleChange.bind(this), true);
      this.initializeAllClientsCheck();
    }
    handleChange(event) {
      const target = event.target;
      const selectAll = target.closest('[name="checkbox-selection-all"]');
      if (selectAll) {
        this.handleSelectAllCheckboxes(selectAll.checked);
        return;
      }
      const userAllClients = target.closest("#user_all_clients");
      if (userAllClients) {
        this.handleAllClientsCheck();
        return;
      }
    }
    handleClick(event) {
      const target = event.target;
      const markAsSent = closestSafe(target, "#btn-mark-as-sent");
      if (markAsSent) {
        this.handleMarkAsSent();
        return;
      }
      const markDraft = closestSafe(target, "#btn-mark-sent-as-draft");
      if (markDraft) {
        this.handleMarkSentAsDraft();
        return;
      }
      const createRecurring = closestSafe(
        target,
        ".create_recurring_confirm_multiple"
      );
      if (createRecurring) {
        this.handleCreateRecurringMultiple(createRecurring);
        return;
      }
      const deleteItemsConfirm = closestSafe(target, ".delete-items-confirm-inv") || closestSafe(target, "#delete-items-confirm-inv");
      if (deleteItemsConfirm) {
        this.handleDeleteInvoiceItems(deleteItemsConfirm);
        return;
      }
      const copyMultiple = closestSafe(target, ".modal_copy_inv_multiple_confirm");
      if (copyMultiple) {
        this.handleCopyMultipleInvoices(copyMultiple);
        return;
      }
      const invToInv = closestSafe(target, "#inv_to_inv_confirm") || closestSafe(target, ".inv_to_inv_confirm");
      if (invToInv) {
        this.handleCopySingleInvoice(invToInv);
        return;
      }
      const invTaxSubmit = closestSafe(target, "#inv_tax_submit");
      if (invTaxSubmit) {
        event.preventDefault();
        this.handleAddInvoiceTax(invTaxSubmit);
        return;
      }
      const pdfWithCustom = closestSafe(target, "#inv_to_pdf_confirm_with_custom_fields");
      if (pdfWithCustom) {
        this.handlePdfExport(true);
        return;
      }
      const pdfWithoutCustom = closestSafe(target, "#inv_to_pdf_confirm_without_custom_fields");
      if (pdfWithoutCustom) {
        this.handlePdfExport(false);
        return;
      }
      const modalPdfWithCustom = closestSafe(target, "#inv_to_modal_pdf_confirm_with_custom_fields");
      if (modalPdfWithCustom) {
        this.handleModalPdfView(true);
        return;
      }
      const modalPdfWithoutCustom = closestSafe(target, "#inv_to_modal_pdf_confirm_without_custom_fields");
      if (modalPdfWithoutCustom) {
        this.handleModalPdfView(false);
        return;
      }
      const htmlWithCustom = closestSafe(target, "#inv_to_html_confirm_with_custom_fields");
      if (htmlWithCustom) {
        this.handleHtmlExport(true);
        return;
      }
      const htmlWithoutCustom = closestSafe(target, "#inv_to_html_confirm_without_custom_fields");
      if (htmlWithoutCustom) {
        this.handleHtmlExport(false);
        return;
      }
      const paymentSubmit = closestSafe(target, "#btn_modal_payment_submit");
      if (paymentSubmit) {
        this.handlePaymentSubmit();
        return;
      }
      const addRowModal = closestSafe(target, ".btn_add_row_modal");
      if (addRowModal) {
        this.handleAddRowModal();
        return;
      }
      const addItemRow = closestSafe(target, ".btn_inv_item_add_row");
      if (addItemRow) {
        this.handleAddInvoiceItemRow();
        return;
      }
      const deleteItem = closestSafe(target, ".btn_delete_item");
      if (deleteItem) {
        this.handleDeleteSingleItem(deleteItem);
        return;
      }
    }
    getCheckedInvoiceIds() {
      const selected = [];
      const table = document.getElementById("table-invoice");
      if (!table) return selected;
      const checkboxes = table.querySelectorAll(
        'input[type="checkbox"]:checked'
      );
      checkboxes.forEach((checkbox) => {
        if (checkbox.id) {
          selected.push(checkbox.id);
        }
      });
      return selected;
    }
    async handleMarkAsSent() {
      const btn = document.getElementById("btn-mark-as-sent");
      const originalHtml = btn?.innerHTML;
      if (btn) {
        setButtonLoading3(btn, true);
      }
      try {
        const selected = this.getCheckedInvoiceIds();
        const url = `${location.origin}/invoice/inv/mark_as_sent`;
        const response = await getJson(url, { keylist: selected });
        const data = parsedata(response);
        if (data.success === 1) {
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
          window.location.reload();
        } else {
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
          window.location.reload();
        }
      } catch (error) {
        console.error("mark_as_sent error", error);
        if (btn && originalHtml) {
          setButtonLoading3(btn, false, originalHtml);
        }
        alert("An error occurred. See console for details.");
      }
    }
    async handleMarkSentAsDraft() {
      const btn = document.getElementById("btn-mark-sent-as-draft");
      const originalHtml = btn?.innerHTML;
      if (btn) {
        setButtonLoading3(btn, true);
      }
      try {
        const selected = this.getCheckedInvoiceIds();
        const url = `${location.origin}/invoice/inv/mark_sent_as_draft`;
        const response = await getJson(url, { keylist: selected });
        const data = parsedata(response);
        if (data.success === 1) {
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
          window.location.reload();
        } else {
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
          window.location.reload();
        }
      } catch (error) {
        console.error("mark_sent_as_draft error", error);
        if (btn && originalHtml) {
          setButtonLoading3(btn, false, originalHtml);
        }
        alert("An error occurred. See console for details.");
      }
    }
    async handleCreateRecurringMultiple(createRecurring) {
      const btn = document.querySelector(".create_recurring_confirm_multiple") || createRecurring;
      const originalHtml = btn?.innerHTML;
      if (btn) {
        btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
        btn.disabled = true;
      }
      try {
        const selected = this.getCheckedInvoiceIds();
        if (selected.length === 0) {
          alert("Please select invoices to create recurring invoices.");
          if (btn && originalHtml) {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
          }
          return;
        }
        const payload = {
          keylist: selected,
          recur_frequency: getFieldValue3("recur_frequency"),
          recur_start_date: getFieldValue3("recur_start_date"),
          recur_end_date: getFieldValue3("recur_end_date")
        };
        if (!payload.recur_frequency || !payload.recur_start_date) {
          alert("Please select frequency and start date.");
          if (btn && originalHtml) {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
          }
          return;
        }
        const url = `${location.origin}/invoice/invrecurring/multiple`;
        const response = await getJson(url, payload);
        const data = parsedata(response);
        if (data.success === 1) {
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
          this.closeModal("create-recurring-multiple");
          setTimeout(() => {
            window.location.reload();
          }, 500);
        } else {
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
          const errorMessage = data.message || "Failed to create recurring invoices. Please try again.";
          alert(errorMessage);
          if (btn && originalHtml) {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
          }
        }
      } catch (error) {
        console.error("invrecurring/multiple error", error);
        if (btn && originalHtml) {
          btn.innerHTML = originalHtml;
          btn.disabled = false;
        }
        alert("An error occurred while creating recurring invoices. See console for details.");
      }
    }
    async handleCopyMultipleInvoices(copyMultiple) {
      const btn = document.querySelector(".modal_copy_inv_multiple_confirm") || copyMultiple;
      const originalHtml = btn?.innerHTML;
      if (btn) {
        btn.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
        btn.disabled = true;
      }
      try {
        const modalCreatedDate = getFieldValue3("modal_created_date");
        const selected = this.getCheckedInvoiceIds();
        if (selected.length === 0) {
          alert("Please select invoices to copy.");
          if (btn && originalHtml) {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
          }
          return;
        }
        const payload = {
          keylist: selected,
          modal_created_date: modalCreatedDate
        };
        const url = `${location.origin}/invoice/inv/multiplecopy`;
        const response = await getJson(url, payload);
        const data = parsedata(response);
        if (data.success === 1) {
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
          window.location.reload();
        } else {
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
          window.location.reload();
        }
      } catch (error) {
        console.error("multiplecopy error", error);
        if (btn && originalHtml) {
          btn.innerHTML = originalHtml;
          btn.disabled = false;
        }
        alert("An error occurred. See console for details.");
      }
    }
    async handleAddInvoiceTax(invTaxSubmit) {
      const btn = document.getElementById("inv_tax_submit");
      const originalHtml = btn?.innerHTML;
      if (btn) {
        btn.innerHTML = '<i class="fa fa-spin fa-spinner"></i>';
        btn.disabled = true;
      }
      try {
        const currentUrl = new URL(location.href);
        const inv_id = currentUrl.pathname.split("/").at(-1) || "";
        const payload = {
          inv_id,
          inv_tax_rate_id: getFieldValue3("inv_tax_rate_id"),
          include_inv_item_tax: getFieldValue3("include_inv_item_tax")
        };
        const url = `${location.origin}/invoice/inv/save_inv_tax_rate`;
        const response = await getJson(url, payload);
        const data = parsedata(response);
        if (data.success === 1) {
          if (btn) btn.innerHTML = '<i class="fa fa-check"></i>';
          this.closeModal("add-inv-tax");
          setTimeout(() => {
            window.location.reload();
          }, 500);
        } else {
          if (btn) btn.innerHTML = '<i class="fa fa-times"></i>';
          alert("Failed to add invoice tax. Please try again.");
          if (btn && originalHtml) {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
          }
        }
      } catch (error) {
        console.error("invoice tax add error", error);
        if (btn && originalHtml) {
          btn.innerHTML = originalHtml;
          btn.disabled = false;
        }
        alert("An error occurred while adding invoice tax. See console for details.");
      }
    }
    async handleCopySingleInvoice(invToInv) {
      const btn = document.querySelector(".inv_to_inv_confirm") || invToInv;
      const originalHtml = btn?.innerHTML;
      if (btn) {
        btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
        btn.disabled = true;
      }
      try {
        const absoluteUrl = new URL(location.href);
        const inv_id = absoluteUrl.pathname.split("/").at(-1) || "";
        const payload = {
          inv_id,
          client_id: getFieldValue3("create_inv_client_id"),
          user_id: getFieldValue3("user_id")
        };
        const url = `${location.origin}/invoice/inv/inv_to_inv_confirm`;
        const response = await getJson(url, payload);
        const data = parsedata(response);
        if (data.success === 1) {
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
          if (data.new_invoice_id) {
            window.location.href = `${location.origin}/invoice/inv/view/${data.new_invoice_id}`;
          } else {
            window.location.reload();
          }
        } else {
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
          window.location.reload();
        }
      } catch (error) {
        console.error("inv_to_inv_confirm error", error);
        if (btn && originalHtml) {
          btn.innerHTML = originalHtml;
          btn.disabled = false;
        }
        alert("An error occurred. See console for details.");
      }
    }
    async handleDeleteInvoiceItems(deleteItemsConfirm) {
      const btn = document.querySelector(".delete-items-confirm-inv") || deleteItemsConfirm;
      const originalHtml = btn?.innerHTML;
      if (btn) {
        btn.innerHTML = '<i class="fa fa-spin fa-spinner"></i>';
        btn.disabled = true;
      }
      try {
        const selected = [];
        const checkboxes = document.querySelectorAll(
          "input[name='item_ids[]']:checked"
        );
        checkboxes.forEach((checkbox) => {
          if (checkbox.value) {
            selected.push(checkbox.value);
          }
        });
        const currentUrl = new URL(location.href);
        const inv_id = currentUrl.pathname.split("/").at(-1) || "";
        if (selected.length === 0) {
          alert("Please select items to delete.");
          if (btn && originalHtml) {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
          }
          return;
        }
        const payload = {
          item_ids: selected,
          inv_id
        };
        const url = `${location.origin}/invoice/invitem/multiple`;
        const response = await getJson(url, payload);
        const data = parsedata(response);
        if (data.success === 1) {
          if (btn) btn.innerHTML = '<i class="fa fa-check"></i>';
          this.closeModal("delete-items");
          setTimeout(() => {
            window.location.reload();
          }, 500);
        } else {
          if (btn) btn.innerHTML = '<i class="fa fa-times"></i>';
          alert("Failed to delete items. Please try again.");
          if (btn && originalHtml) {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
          }
        }
      } catch (error) {
        console.error("delete items error", error);
        if (btn && originalHtml) {
          btn.innerHTML = originalHtml;
          btn.disabled = false;
        }
        alert("An error occurred while deleting items. See console for details.");
      }
    }
    handlePdfExport(withCustomFields) {
      const endpoint = withCustomFields ? "1" : "0";
      const url = `${location.origin}/invoice/inv/pdf/${endpoint}`;
      window.open(url, "_blank");
    }
    handleModalPdfView(withCustomFields) {
      const endpoint = withCustomFields ? "1" : "0";
      const url = `${location.origin}/invoice/inv/pdf/${endpoint}`;
      const iframe = document.getElementById("modal-view-inv-pdf");
      if (iframe) {
        iframe.src = url;
      }
      try {
        if (typeof window.bootstrap?.Modal !== "undefined") {
          const modalEl = document.getElementById("modal-layout-modal-pdf-inv");
          if (modalEl) {
            const modal = new window.bootstrap.Modal(modalEl);
            modal.show();
          }
        }
      } catch (e) {
        console.warn("Failed to open PDF modal:", e);
      }
    }
    handleHtmlExport(withCustomFields) {
      const endpoint = withCustomFields ? "1" : "0";
      const url = `${location.origin}/invoice/inv/html/${endpoint}`;
      window.open(url, "_blank");
    }
    async handlePaymentSubmit() {
      const url = `${location.origin}/invoice/payment/add_with_ajax`;
      const btn = document.getElementById("btn_modal_payment_submit");
      const originalHtml = btn?.innerHTML;
      if (btn) {
        btn.innerHTML = '<i class="fa fa-spin fa-spinner"></i>';
        btn.disabled = true;
      }
      try {
        const payload = {
          // Add payment form fields here based on the actual form structure
          // This is a placeholder - you'll need to adjust based on the actual payment form
          amount: getFieldValue3("payment_amount"),
          payment_method: getFieldValue3("payment_method"),
          payment_date: getFieldValue3("payment_date")
          // Add other payment fields as needed
        };
        const response = await getJson(url, payload);
        const data = parsedata(response);
        if (data.success === 1) {
          if (btn) btn.innerHTML = '<i class="fa fa-check"></i>';
          this.closeModal("payment-modal");
          setTimeout(() => {
            window.location.reload();
          }, 500);
        } else {
          if (btn && originalHtml) {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
          }
          alert("Failed to process payment. Please try again.");
        }
      } catch (error) {
        console.error("Payment submission error:", error);
        if (btn && originalHtml) {
          btn.innerHTML = originalHtml;
          btn.disabled = false;
        }
        alert("An error occurred while processing payment. See console for details.");
      }
    }
    handleAddRowModal() {
      const currentUrl = new URL(location.href);
      const inv_id = currentUrl.pathname.split("/").at(-1) || "";
      const url = `${location.origin}/invoice/invitem/add/${inv_id}`;
      const modalPlaceholder = document.getElementById("modal-placeholder-invitem");
      if (modalPlaceholder) {
        fetch(url, { credentials: "same-origin" }).then((response) => response.text()).then((html) => {
          modalPlaceholder.textContent = "";
          const tempDiv = document.createElement("div");
          tempDiv.textContent = html;
          const fragment = document.createDocumentFragment();
          const parser = new DOMParser();
          try {
            const doc = parser.parseFromString(html, "text/html");
            if (doc && doc.body) {
              while (doc.body.firstChild) {
                fragment.appendChild(doc.body.firstChild);
              }
              modalPlaceholder.appendChild(fragment);
            }
          } catch (e) {
            console.error("HTML parsing error:", e);
            modalPlaceholder.textContent = "Error loading content";
          }
        }).catch((error) => {
          console.error("Failed to load modal content:", error);
          modalPlaceholder.textContent = "Failed to load item form. Please try again.";
        });
      }
    }
    handleAddInvoiceItemRow() {
      const newRow = document.getElementById("new_row");
      const itemTable = document.getElementById("item_table");
      if (newRow && itemTable) {
        const clonedRow = newRow.cloneNode(true);
        clonedRow.removeAttribute("id");
        clonedRow.classList.add("item");
        clonedRow.style.display = "block";
        itemTable.appendChild(clonedRow);
      }
    }
    async handleDeleteSingleItem(deleteItem) {
      const itemId = deleteItem.getAttribute("data-id");
      if (!itemId) {
        const itemRow = deleteItem.closest(".item");
        if (itemRow) {
          itemRow.remove();
        }
        return;
      }
      try {
        const url = `${location.origin}/invoice/inv/delete_item/${itemId}`;
        const response = await getJson(url, { id: itemId });
        const data = parsedata(response);
        if (data.success === 1) {
          const itemRow = deleteItem.closest(".item");
          if (itemRow) {
            itemRow.remove();
          }
          alert("Deleted");
          window.location.reload();
        } else {
          alert("Failed to delete item. Please try again.");
        }
      } catch (error) {
        console.error("Delete item error:", error);
        alert("An error occurred while deleting the item. Please try again.");
      }
    }
    handleSelectAllCheckboxes(checked) {
      const checkboxes = document.querySelectorAll('input[type="checkbox"]');
      checkboxes.forEach((checkbox) => {
        checkbox.checked = checked;
      });
    }
    initializeAllClientsCheck() {
      this.handleAllClientsCheck();
    }
    handleAllClientsCheck() {
      const userAllClientsCheckbox = document.getElementById("user_all_clients");
      const listClientElement = document.getElementById("list_client");
      if (userAllClientsCheckbox && listClientElement) {
        if (userAllClientsCheckbox.checked) {
          listClientElement.style.display = "none";
        } else {
          listClientElement.style.display = "block";
        }
      }
    }
    closeModal(modalId) {
      try {
        if (typeof window.bootstrap?.Modal !== "undefined") {
          const modalEl = document.getElementById(modalId);
          if (modalEl) {
            const modalInstance = window.bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) {
              modalInstance.hide();
            }
          }
        }
      } catch (e) {
        console.warn("Failed to close modal:", e);
      }
    }
  };

  // src/typescript/product.ts
  function setButtonLoading4(buttons, isLoading) {
    buttons.forEach((button) => {
      if (isLoading) {
        button.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
      } else {
        button.innerHTML = '<h6 class="text-center"><i class="fa fa-check"></i></h6>';
      }
    });
  }
  function setButtonError(buttons) {
    buttons.forEach((button) => {
      button.innerHTML = '<h6 class="text-center"><i class="fa fa-error"></i></h6>';
    });
  }
  var ProductHandler = class {
    constructor() {
      this.bindEventListeners();
      this.exposeGlobalFunctions();
      this.initializeComponents();
    }
    bindEventListeners() {
      document.addEventListener("click", this.handleClick.bind(this), true);
    }
    handleClick(event) {
      const target = event.target;
      if (target.closest("#product_filters_submit")) {
        this.submitProductFilters(event);
        return;
      }
      if (target.closest(".select-items-confirm-quote")) {
        event.preventDefault();
        this.handleQuoteConfirm();
        return;
      }
      if (target.closest(".select-items-confirm-inv")) {
        event.preventDefault();
        this.handleInvoiceConfirm();
        return;
      }
      const productRow = target.closest(".product");
      if (productRow && target.tagName !== "INPUT") {
        const checkbox = productRow.querySelector('input[type="checkbox"]');
        if (checkbox) {
          checkbox.click();
        }
        return;
      }
      if (target.matches("input[name='product_ids[]']")) {
        this.updateButtonStates();
      }
    }
    initializeComponents() {
      if (typeof window.TomSelect !== "undefined") {
        document.querySelectorAll(".simple-select").forEach((el) => {
          if (!el._tomselect) {
            new window.TomSelect(el, {});
            el._tomselect = true;
          }
        });
      }
      this.updateButtonStates();
    }
    updateButtonStates() {
      const checkedInputs = document.querySelectorAll("input[name='product_ids[]']:checked");
      const hasChecked = checkedInputs.length > 0;
      const quoteBtn = document.querySelector(".select-items-confirm-quote");
      const invBtn = document.querySelector(".select-items-confirm-inv");
      if (quoteBtn) {
        quoteBtn.disabled = !hasChecked;
      }
      if (invBtn) {
        invBtn.disabled = !hasChecked;
      }
    }
    /**
     * Filter table rows by SKU (mirrors original tableFunction)
     */
    filterTableBySku() {
      const inputEl = document.getElementById("filter_product_sku");
      if (!inputEl) return;
      const input = inputEl.value || "";
      const filter = input.toUpperCase();
      const table = document.getElementById("table-product");
      if (!table) return;
      const rows = table.getElementsByTagName("tr");
      for (let i = 0; i < rows.length; i++) {
        const cell = rows[i].getElementsByTagName("td")[2];
        if (cell) {
          const textValue = cell.textContent || cell.innerText || "";
          if (textValue.toUpperCase().indexOf(filter) > -1) {
            rows[i].style.display = "";
          } else {
            rows[i].style.display = "none";
          }
        }
      }
    }
    /**
     * Perform the product search request and update UI
     */
    async submitProductFilters(event) {
      if (event?.preventDefault) {
        event.preventDefault();
      }
      const url = `${location.origin}/invoice/product/search`;
      const buttons = document.querySelectorAll(
        ".product_filters_submit"
      );
      setButtonLoading4(buttons, true);
      try {
        const productSkuInput = document.getElementById(
          "filter_product_sku"
        );
        const productSku = productSkuInput?.value || "";
        const payload = {
          product_sku: productSku
        };
        const response = await getJson(url, payload);
        const data = parsedata(response);
        if (data.success === 1) {
          this.filterTableBySku();
          this.hideSummaryBar();
          setButtonLoading4(buttons, false);
        } else {
          setButtonError(buttons);
          if (data.message) {
            alert(data.message);
          }
        }
      } catch (error) {
        console.error("product search failed", error);
        setButtonError(buttons);
        alert("An error occurred while searching products. See console for details.");
      }
    }
    /**
     * Hide the summary bar after filtering
     */
    hideSummaryBar() {
      const summary = document.querySelector(".mt-3.me-3.summary.text-end");
      if (summary) {
        summary.style.visibility = "hidden";
      }
    }
    async handleQuoteConfirm() {
      const absoluteUrl = new URL(window.location.href);
      const btn = document.querySelector(".select-items-confirm-quote");
      this.setSecureButtonContent(btn, "h2", "text-center", "fa fa-spin fa-spinner");
      const productIds = [];
      const quoteId = (absoluteUrl.pathname.split("/").at(-1) || "").replace(/[^0-9]/g, "");
      document.querySelectorAll("input[name='product_ids[]']:checked").forEach((input) => {
        const value = parseInt(input.value);
        if (!isNaN(value)) {
          productIds.push(value);
        }
      });
      const sortedProductIds = productIds.toSorted((a, b) => a - b);
      console.log("Processing products in sorted order:", sortedProductIds);
      let url = `/invoice/product/selection_quote?quote_id=${quoteId}`;
      sortedProductIds.forEach((id) => {
        url += `&product_ids[]=${id}`;
      });
      try {
        const response = await fetch(url, {
          method: "GET",
          headers: {
            "Content-Type": "application/json; charset=utf-8",
            "X-Requested-With": "XMLHttpRequest"
          }
        });
        const data = await response.json();
        this.processProducts(data);
        this.setSecureButtonContent(btn, "h2", "text-center", "fa fa-check");
        window.location.reload();
      } catch (error) {
        console.error("Error:", error);
        this.setSecureButtonContent(btn, "h2", "text-center", "fa fa-times");
      }
    }
    async handleInvoiceConfirm() {
      const absoluteUrl = new URL(window.location.href);
      const btn = document.querySelector(".select-items-confirm-inv");
      this.setSecureButtonContent(btn, "h2", "text-center", "fa fa-spin fa-spinner");
      const productIds = [];
      const invId = absoluteUrl.pathname.split("/").at(-1) || "";
      document.querySelectorAll("input[name='product_ids[]']:checked").forEach((input) => {
        const value = parseInt(input.value);
        if (!isNaN(value)) {
          productIds.push(value);
        }
      });
      const sortedProductIds = productIds.toSorted((a, b) => a - b);
      let url = `/invoice/product/selection_inv?inv_id=${invId}`;
      sortedProductIds.forEach((id) => {
        url += `&product_ids[]=${id}`;
      });
      try {
        const response = await fetch(url, {
          method: "GET",
          headers: {
            "Content-Type": "application/json; charset=utf-8",
            "X-Requested-With": "XMLHttpRequest"
          }
        });
        const data = await response.json();
        this.processProducts(data);
        this.setSecureButtonContent(btn, "h2", "text-center", "fa fa-check");
        window.location.reload();
      } catch (error) {
        console.error("Error:", error);
        this.setSecureButtonContent(btn, "h2", "text-center", "fa fa-times");
      }
    }
    processProducts(products) {
      console.log("Processing", Object.keys(products).length, "products");
      const productsByTaxRate = Object.groupBy(
        Object.entries(products).map(([key, product]) => ({ key, ...product })),
        (product) => product.tax_rate_id || "default"
      );
      console.log("Products grouped by tax rate:", Object.keys(productsByTaxRate));
      for (const key in products) {
        console.log("Processing product key:", key);
        const product = products[key];
        if (!product || typeof product !== "object") continue;
        const currentTaxRateId = product.tax_rate_id;
        let productDefaultTaxRateId;
        if (!currentTaxRateId) {
          const defaultTaxRateEl = document.getElementById("default_item_tax_rate");
          productDefaultTaxRateId = defaultTaxRateEl ? defaultTaxRateEl.getAttribute("value") || "" : "";
        } else {
          productDefaultTaxRateId = currentTaxRateId;
        }
        const lastItemRow = document.querySelector("#item_table tbody:last-of-type");
        if (lastItemRow) {
          const itemName = lastItemRow.querySelector("input[name=item_name]");
          if (itemName) itemName.value = product.product_name;
          const itemDesc = lastItemRow.querySelector("textarea[name=item_description]");
          if (itemDesc) itemDesc.value = product.product_description;
          const itemPrice = lastItemRow.querySelector("input[name=item_price]");
          if (itemPrice) itemPrice.value = product.product_price;
          const itemQty = lastItemRow.querySelector("input[name=item_quantity]");
          if (itemQty) itemQty.value = "1";
          const itemTaxRate = lastItemRow.querySelector("select[name=item_tax_rate_id]");
          if (itemTaxRate) itemTaxRate.value = productDefaultTaxRateId;
          const itemProductId = lastItemRow.querySelector("input[name=item_product_id]");
          if (itemProductId) itemProductId.value = product.id;
          const itemUnitId = lastItemRow.querySelector("select[name=item_product_unit_id]");
          if (itemUnitId) itemUnitId.value = product.unit_id;
        }
      }
    }
    setSecureButtonContent(button, tagName, className, iconClass) {
      if (!button) return;
      while (button.firstChild) {
        button.removeChild(button.firstChild);
      }
      const element = document.createElement(tagName);
      if (className) element.className = className;
      const icon = document.createElement("i");
      if (iconClass) icon.className = iconClass;
      element.appendChild(icon);
      button.appendChild(element);
    }
    /**
     * Expose global functions for compatibility with existing code
     */
    exposeGlobalFunctions() {
      window.productTableFilter = this.filterTableBySku.bind(this);
    }
  };

  // src/typescript/tasks.ts
  var TaskHandler = class {
    constructor() {
      this.bindEventListeners();
      this.initializeComponents();
    }
    bindEventListeners() {
      document.addEventListener("click", this.handleClick.bind(this), true);
      document.addEventListener("change", this.handleChange.bind(this), true);
      document.addEventListener("keydown", this.handleKeydown.bind(this), true);
      document.addEventListener("shown.bs.modal", this.handleModalShown.bind(this), true);
    }
    handleClick(event) {
      const target = event.target;
      const taskRow = target.closest("#tasks_table tr, .task, .task-row");
      if (taskRow) {
        this.rowClickToggle(event);
        return;
      }
      const confirmTask = target.closest(".select-items-confirm-task, .select-items-confirm-task-inv, .select-items-confirm-task-quote");
      if (confirmTask) {
        this.handleSelectItemsConfirmTask(confirmTask);
        return;
      }
      const resetButton = target.closest("#task-reset-button-inv");
      if (resetButton) {
        this.handleTaskReset();
        return;
      }
    }
    handleChange(event) {
      const target = event.target;
      if (target.matches("input[name='task_ids[]']")) {
        const container = target.closest("#tasks_table") || document;
        this.updateSelectTaskButtonState(container);
      }
    }
    handleKeydown(event) {
      if (event.key === "Enter") {
        const active = document.activeElement;
        if (active && active.id === "filter_task_inv") {
          const btn = document.getElementById("filter-button-inv");
          if (btn) {
            btn.click();
            event.preventDefault();
          }
        }
      }
    }
    initializeComponents() {
      this.hideSelectedTasks();
      this.updateSelectTaskButtonState(document);
    }
    /**
     * Handle modal shown event to reinitialize components
     */
    handleModalShown(event) {
      const target = event.target;
      if (target.id === "modal-choose-tasks" || target.id === "modal-choose-tasks-inv") {
        console.log("Task modal shown, reinitializing components...");
        this.hideSelectedTasks();
        this.updateSelectTaskButtonState(document);
      }
    }
    /**
     * Hide already-selected tasks (based on .item-task-id values)
     */
    hideSelectedTasks() {
      const selectedTasks = [];
      document.querySelectorAll(".item-task-id").forEach((el) => {
        const input = el;
        const currentVal = input.value || "";
        if (currentVal.length) {
          const taskId = parseInt(currentVal, 10);
          if (!isNaN(taskId)) {
            selectedTasks.push(taskId);
          }
        }
      });
      let hiddenTasks = 0;
      document.querySelectorAll(".modal-task-id").forEach((el) => {
        const idAttr = el.id || "";
        const idNum = parseInt(idAttr.replace("task-id-", ""), 10);
        if (!Number.isNaN(idNum) && selectedTasks.includes(idNum)) {
          const row = el.closest("tr") || el.parentElement && el.parentElement.parentElement;
          if (row) {
            row.style.display = "none";
            hiddenTasks++;
          }
        }
      });
      const taskRows = document.querySelectorAll(".task-row");
      if (hiddenTasks >= taskRows.length) {
        const submitBtn = document.getElementById("task-modal-submit") || document.getElementById("task-modal-submit-quote");
        if (submitBtn) {
          submitBtn.style.display = "none";
        }
      }
    }
    /**
     * Toggle checkbox when clicking on row (unless click was on checkbox)
     */
    rowClickToggle(event) {
      const row = event.target.closest("#tasks_table tr, .task-row, .task");
      if (!row) return;
      const target = event.target;
      if (target.type !== "checkbox") {
        const checkbox = row.querySelector('input[type="checkbox"]');
        if (checkbox) {
          checkbox.checked = !checkbox.checked;
          checkbox.dispatchEvent(new Event("change", { bubbles: true }));
        }
      }
    }
    /**
     * Enable/disable select button based on checked tasks
     */
    updateSelectTaskButtonState(root) {
      const ctx = root || document;
      const checkboxes = ctx.querySelectorAll("input[name='task_ids[]']");
      const checkedBoxes = ctx.querySelectorAll("input[name='task_ids[]']:checked");
      const anyChecked = checkedBoxes.length > 0;
      console.log(`\u{1F50D} Task button state update: ${checkboxes.length} total checkboxes, ${checkedBoxes.length} checked, anyChecked: ${anyChecked}`);
      let buttons;
      buttons = ctx.querySelectorAll(".select-items-confirm-task, .select-items-confirm-task-inv, .select-items-confirm-task-quote");
      if (buttons.length === 0) {
        buttons = document.querySelectorAll(".select-items-confirm-task, .select-items-confirm-task-inv, .select-items-confirm-task-quote");
      }
      if (buttons.length === 0) {
        const buttonById = document.getElementById("task-modal-submit") || document.getElementById("task-modal-submit-quote");
        if (buttonById) {
          buttons = [buttonById];
          console.log("\u{1F50D} Found button by ID fallback");
        }
      }
      if (buttons.length === 0) {
        const modals = document.querySelectorAll(".modal");
        modals.forEach((modal) => {
          const modalButtons = modal.querySelectorAll(".select-items-confirm-task, .select-items-confirm-task-inv, .select-items-confirm-task-quote, #task-modal-submit, #task-modal-submit-quote");
          if (modalButtons.length > 0) {
            buttons = modalButtons;
            console.log("\u{1F50D} Found button in modal fallback");
          }
        });
      }
      console.log(`\u{1F50D} Found ${buttons.length} task submit buttons`);
      buttons.forEach((btn) => {
        const button = btn;
        if (anyChecked) {
          button.removeAttribute("disabled");
          button.removeAttribute("aria-disabled");
          button.disabled = false;
          console.log("\u2705 Task submit button enabled");
        } else {
          button.setAttribute("disabled", "true");
          button.setAttribute("aria-disabled", "true");
          button.disabled = true;
          console.log("\u274C Task submit button disabled");
        }
      });
    }
    /**
     * Handle confirm click: collect selected task ids and send to server, then populate items and reload
     */
    async handleSelectItemsConfirmTask(btn) {
      const absoluteUrl = new URL(location.href);
      const entityId = absoluteUrl.pathname.split("/").at(-1) || "";
      const isQuotePage = absoluteUrl.pathname.includes("/quote/");
      const isInvoicePage = absoluteUrl.pathname.includes("/inv/");
      const taskIds = Array.from(
        document.querySelectorAll("input[name='task_ids[]']:checked")
      ).map((el) => parseInt(el.value, 10)).filter((id) => !isNaN(id));
      if (taskIds.length === 0) return;
      const sortedTaskIds = taskIds.toSorted((a, b) => a - b);
      console.log("Processing tasks in sorted order:", sortedTaskIds);
      const originalHtml = btn.innerHTML;
      btn.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
      btn.disabled = true;
      const params = new URLSearchParams();
      sortedTaskIds.forEach((id) => {
        params.append("task_ids[]", id.toString());
      });
      if (isQuotePage) {
        params.append("quote_id", entityId);
      } else {
        params.append("inv_id", entityId);
      }
      const endpoint = isQuotePage ? "selection_quote" : "selection_inv";
      try {
        const response = await fetch(`/invoice/task/${endpoint}?${params.toString()}`, {
          method: "GET",
          credentials: "same-origin",
          cache: "no-store",
          headers: { "Accept": "application/json" }
        });
        if (!response.ok) {
          throw new Error(`Network response not ok: ${response.status}`);
        }
        const text = await response.text();
        let data;
        try {
          data = JSON.parse(text);
        } catch (e) {
          data = text;
        }
        const tasks = parsedata(data);
        this.processTasks(tasks);
        btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
        location.reload();
      } catch (error) {
        console.error("selection_inv failed", error);
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        const userError = new Error("An error occurred while adding tasks to invoice. See console for details.", {
          cause: error
        });
        alert(userError.message);
      }
    }
    /**
     * Process tasks and populate form fields
     */
    processTasks(tasks) {
      let productDefaultTaxRateId = null;
      const tasksByTaxRate = Object.groupBy(
        Object.entries(tasks).map(([key, task]) => ({ key, ...task })),
        (task) => task.tax_rate_id || "default"
      );
      console.log("Tasks grouped by tax rate:", Object.keys(tasksByTaxRate));
      Object.entries(tasks).toReversed().forEach(([key, task]) => {
        const currentTaxRateId = task.tax_rate_id;
        if (!currentTaxRateId) {
          const defaultTaxEl = document.getElementById("default_item_tax_rate") || document.querySelector("#default_item_tax_rate");
          productDefaultTaxRateId = defaultTaxEl ? defaultTaxEl.getAttribute("value") : "";
        } else {
          productDefaultTaxRateId = currentTaxRateId;
        }
        const lastTbody = document.querySelector("#item_table tbody:last-of-type") || document.querySelector("#item_table tbody");
        if (!lastTbody) return;
        const nameEl = lastTbody.querySelector('input[name="item_name"]');
        const descEl = lastTbody.querySelector('textarea[name="item_description"]');
        const priceEl = lastTbody.querySelector('input[name="item_price"]');
        const qtyEl = lastTbody.querySelector('input[name="item_quantity"]');
        const taxEl = lastTbody.querySelector('select[name="item_tax_rate_id"]');
        const taskIdEl = lastTbody.querySelector('input[name="item_task_id"]');
        if (nameEl) nameEl.value = task.name || "";
        if (descEl) descEl.value = task.description || "";
        if (priceEl) priceEl.value = task.price || "";
        if (qtyEl) qtyEl.value = "1";
        if (taxEl) taxEl.value = productDefaultTaxRateId || "";
        if (taskIdEl) taskIdEl.value = task.id || "";
      });
    }
    /**
     * Handle task reset button
     */
    async handleTaskReset() {
      const tasksTable = document.querySelector("#tasks_table");
      if (!tasksTable) return;
      const lookupUrl = `${location.origin}/invoice/task/lookup?rt=true`;
      tasksTable.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
      const { promise, resolve, reject } = Promise.withResolvers();
      const timeoutId = setTimeout(() => {
        reject(new Error("Task lookup timeout", {
          cause: "Server did not respond within expected timeframe"
        }));
      }, 1e4);
      try {
        const response = await fetch(lookupUrl, {
          cache: "no-store",
          credentials: "same-origin"
        });
        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, "text/html");
        const fragment = document.createDocumentFragment();
        Array.from(doc.body.children).toReversed().forEach((child) => {
          fragment.insertBefore(child, fragment.firstChild);
        });
        tasksTable.innerHTML = "";
        tasksTable.appendChild(fragment);
        this.updateSelectTaskButtonState(tasksTable);
        clearTimeout(timeoutId);
        resolve();
      } catch (error) {
        clearTimeout(timeoutId);
        console.error("task lookup load failed", error);
        reject(error);
      }
      promise.then(() => {
        document.querySelectorAll(".select-items-confirm-task").forEach((btn) => {
          btn.removeAttribute("disabled");
        });
      }).catch((error) => {
        console.error("Task reset failed:", error);
        document.querySelectorAll(".select-items-confirm-task").forEach((btn) => {
          btn.removeAttribute("disabled");
        });
      });
    }
  };

  // src/typescript/salesorder.ts
  function secureInsertHTML2(element, html) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, "text/html");
    const fragment = document.createDocumentFragment();
    Array.from(doc.body.children).forEach((child) => fragment.appendChild(child));
    element.innerHTML = "";
    element.appendChild(fragment);
  }
  function secureReload3() {
    window.location.reload();
  }
  var SalesOrderHandler = class {
    constructor() {
      this.bindEventListeners();
      this.initializeOnLoad();
    }
    bindEventListeners() {
      document.addEventListener("click", this.handleClick.bind(this), true);
    }
    initializeOnLoad() {
      if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", () => {
          this.initSelects();
        });
      } else {
        this.initSelects();
      }
    }
    handleClick(event) {
      const target = event.target;
      if (target.matches("#salesorder_to_pdf_confirm_with_custom_fields") || target.closest("#salesorder_to_pdf_confirm_with_custom_fields")) {
        this.handlePdfExport(true);
        return;
      }
      if (target.matches("#salesorder_to_pdf_confirm_without_custom_fields") || target.closest("#salesorder_to_pdf_confirm_without_custom_fields")) {
        this.handlePdfExport(false);
        return;
      }
      if (target.matches("#so_to_invoice_confirm") || target.closest("#so_to_invoice_confirm")) {
        this.handleSoToInvoiceConversion();
        return;
      }
      const openModalBtn = target.closest(".open-salesorder-modal");
      if (openModalBtn) {
        this.handleOpenModal(openModalBtn);
        return;
      }
      const saveBtn = target.closest(".salesorder-save");
      if (saveBtn) {
        this.handleSaveSalesOrder();
        return;
      }
    }
    /**
     * Initialize Tom Select if present for salesorder selects
     */
    initSelects() {
      if (typeof window.TomSelect === "undefined") return;
      const selects = document.querySelectorAll(
        ".simple-select"
      );
      selects.forEach((element) => {
        if (!element._tomselect) {
          try {
            new window.TomSelect(element, {});
            element._tomselect = true;
          } catch (error) {
            console.warn("Failed to initialize TomSelect:", error);
          }
        }
      });
    }
    /**
     * Handle opening the sales order modal
     */
    async handleOpenModal(openBtn) {
      const url = openBtn.dataset.url || `${location.origin}/invoice/salesorder/modal`;
      const targetId = openBtn.dataset.target || "modal-placeholder-salesorder";
      const target = document.getElementById(targetId);
      if (!target) {
        console.error(`Modal target element not found: ${targetId}`);
        return;
      }
      try {
        const response = await fetch(url, { cache: "no-store", credentials: "same-origin" });
        const html = await response.text();
        secureInsertHTML2(target, html);
        const modalEl = target.querySelector(".modal");
        if (modalEl && window.bootstrap?.Modal) {
          const modalInstance = new window.bootstrap.Modal(modalEl);
          modalInstance.show();
        }
        this.initSelects();
      } catch (error) {
        console.error("Failed to load sales order modal:", error);
        alert("Failed to load modal. Please try again.");
      }
    }
    /**
     * Handle PDF export with or without custom fields
     */
    handlePdfExport(withCustomFields) {
      const url = location.origin + "/invoice/salesorder/pdf/" + (withCustomFields ? "1" : "0");
      window.location.reload();
      window.open(url, "_blank");
    }
    /**
     * Handle Sales Order to Invoice conversion
     */
    async handleSoToInvoiceConversion() {
      const btn = document.querySelector(".so_to_invoice_confirm");
      if (btn) {
        btn.innerHTML = '<i class="fa fa-spin fa-spinner fa-margin"></i>';
      }
      const soIdEl = document.getElementById("so_id");
      const clientIdEl = document.getElementById("client_id");
      const groupIdEl = document.getElementById("group_id");
      const passwordEl = document.getElementById("password");
      if (!soIdEl?.value || !clientIdEl?.value || !groupIdEl?.value) {
        console.error("Required fields missing for SO to Invoice conversion");
        alert("Missing required data for conversion");
        return;
      }
      const payload = {
        so_id: soIdEl.value,
        client_id: clientIdEl.value,
        group_id: groupIdEl.value,
        password: passwordEl?.value || ""
      };
      try {
        const url = location.origin + "/invoice/salesorder/so_to_invoice_confirm";
        const response = await getJson(url, payload);
        if (response && response.success === 1) {
          if (btn) {
            btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
          }
          if (response.inv_id) {
            window.location.href = `${location.origin}/invoice/inv/view/${response.inv_id}`;
          } else {
            secureReload3();
          }
        } else {
          if (response?.validation_errors) {
            document.querySelectorAll(".control-group").forEach((group) => {
              group.classList.remove("error");
            });
            Object.entries(response.validation_errors).forEach(([key, error]) => {
              const field = document.getElementById(key);
              if (field?.parentElement) {
                field.parentElement.classList.add("has-error");
              }
            });
          }
          if (btn) {
            btn.innerHTML = '<h6 class="text-center"><i class="fa fa-check"></i></h6>';
          }
        }
      } catch (error) {
        console.error("SO to Invoice conversion failed:", error);
        if (btn) {
          btn.innerHTML = '<h6 class="text-center"><i class="fa fa-check"></i></h6>';
        }
        alert("An error occurred during conversion. Please try again.");
      }
    }
    /**
     * Handle saving the sales order form
     */
    async handleSaveSalesOrder() {
      const form = document.querySelector("#salesorder_form");
      if (!form) {
        console.error("Sales order form not found");
        return;
      }
      try {
        const action = form.getAttribute("action") || `${location.origin}/invoice/salesorder/save`;
        const formData = new FormData(form);
        const params = new URLSearchParams();
        formData.forEach((value, key) => {
          params.append(key, value.toString());
        });
        const url = `${action}?${params.toString()}`;
        const response = await fetch(url, {
          cache: "no-store",
          credentials: "same-origin",
          headers: {
            Accept: "application/json"
          }
        });
        const data = await response.json();
        const parsedResponse = parsedata(data);
        if (parsedResponse.success === 1) {
          window.location.reload();
        } else {
          const message = parsedResponse.message || "Save failed";
          alert(message);
        }
      } catch (error) {
        console.error("Sales order save failed:", error);
        alert("An error occurred while saving. Please try again.");
      }
    }
  };

  // src/typescript/family.ts
  var FamilyHandler = class {
    constructor() {
      this.bindEventListeners();
    }
    bindEventListeners() {
      if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", this.initializeSelectors.bind(this));
      } else {
        this.initializeSelectors();
      }
      document.addEventListener("click", this.handleClick.bind(this), true);
    }
    handleClick(event) {
      const target = event.target;
      if (target.closest("#process-generate-products")) {
        this.processProductGeneration();
        return;
      }
    }
    initializeSelectors() {
      const primarySelect = document.getElementById(
        "family-category-primary-id"
      );
      const secondarySelect = document.getElementById(
        "family-category-secondary-id"
      );
      if (primarySelect) {
        primarySelect.addEventListener("change", this.onPrimaryChange.bind(this), false);
        this.initializeSelector(() => this.onPrimaryChange());
      }
      if (secondarySelect) {
        secondarySelect.addEventListener("change", this.onSecondaryChange.bind(this), false);
        this.initializeSelector(() => this.onSecondaryChange());
      }
    }
    /**
     * Initialize selector with deferred execution using ES2024 Promise.withResolvers
     * Enhanced with error handling and timeout protection
     */
    initializeSelector(callback) {
      const { promise, resolve, reject } = Promise.withResolvers();
      const timeoutId = setTimeout(() => {
        reject(new Error("Selector initialization timeout", {
          cause: "DOM not ready within expected timeframe"
        }));
      }, 5e3);
      setTimeout(() => {
        clearTimeout(timeoutId);
        resolve();
      }, 0);
      promise.then(() => callback()).catch((error) => {
        console.error("Selector initialization failed:", error);
        try {
          callback();
        } catch (callbackError) {
          console.error("Callback execution failed:", callbackError);
        }
      });
    }
    /**
     * Populate a <select> element with options from an object { key: value, ... }
     */
    populateSelect(selectEl, items, promptText) {
      if (!selectEl) return;
      selectEl.innerHTML = "";
      const promptOption = document.createElement("option");
      promptOption.value = "";
      promptOption.textContent = promptText || "None";
      selectEl.appendChild(promptOption);
      if (!items) return;
      if (Array.isArray(items)) {
        items.forEach((value, index) => {
          const option = document.createElement("option");
          option.value = index.toString();
          option.textContent = value;
          selectEl.appendChild(option);
        });
      } else {
        Object.entries(items).forEach(([key, value]) => {
          const option = document.createElement("option");
          option.value = key;
          option.textContent = value;
          selectEl.appendChild(option);
        });
      }
    }
    /**
     * Handler: when primary category changes, load secondary categories
     */
    async onPrimaryChange() {
      const primarySelect = document.getElementById(
        "family-category-primary-id"
      );
      if (!primarySelect) return;
      const primaryCategoryId = primarySelect.value || "";
      const url = `${location.origin}/invoice/family/secondaries/${encodeURIComponent(primaryCategoryId)}`;
      try {
        const payload = {
          category_primary_id: primaryCategoryId
        };
        const response = await getJson(url, payload);
        const data = parsedata(response);
        if (data.success === 1) {
          const secondaryCategories = data.secondary_categories || {};
          const secondaryDropdown = document.getElementById(
            "family-category-secondary-id"
          );
          this.populateSelect(secondaryDropdown, secondaryCategories, "None");
          if (secondaryDropdown) {
            const changeEvent = new Event("change", { bubbles: true });
            secondaryDropdown.dispatchEvent(changeEvent);
          }
        } else {
          this.populateSelect(
            document.getElementById("family-category-secondary-id"),
            {},
            "None"
          );
          this.populateSelect(
            document.getElementById("family-name"),
            {},
            "None"
          );
        }
      } catch (error) {
        console.error("Error loading secondary categories", error);
        this.populateSelect(
          document.getElementById("family-category-secondary-id"),
          {},
          "None"
        );
        this.populateSelect(
          document.getElementById("family-name"),
          {},
          "None"
        );
      }
    }
    /**
     * Handler: when secondary category changes, load family names
     */
    async onSecondaryChange() {
      const secondarySelect = document.getElementById(
        "family-category-secondary-id"
      );
      if (!secondarySelect) return;
      const secondaryCategoryId = secondarySelect.value || "";
      const url = `${location.origin}/invoice/family/names/${encodeURIComponent(secondaryCategoryId)}`;
      try {
        const payload = {
          category_secondary_id: secondaryCategoryId
        };
        const response = await getJson(url, payload);
        const data = parsedata(response);
        if (data.success === 1) {
          const familyNames = data.family_names || {};
          const familyNameDropdown = document.getElementById(
            "family-name"
          );
          this.populateSelect(familyNameDropdown, familyNames, "None");
        } else {
          this.populateSelect(
            document.getElementById("family-name"),
            {},
            "None"
          );
        }
      } catch (error) {
        console.error("Error loading family names", error);
        this.populateSelect(
          document.getElementById("family-name"),
          {},
          "None"
        );
      }
    }
    /**
     * Get family data from checked checkboxes
     */
    getFamilyDataFromCheckedBoxes() {
      const families = [];
      const checkedBoxes = document.querySelectorAll('input[name="family_ids[]"]:checked');
      checkedBoxes.forEach((checkbox) => {
        const row = checkbox.closest("tr");
        if (!row) return;
        const familyId = checkbox.value;
        const familyNameCell = row.querySelector("[data-family-name]");
        const familyPrefixCell = row.querySelector("[data-family-prefix]");
        const familyCommaListCell = row.querySelector("[data-family-commalist]");
        const familyData = {
          family_id: familyId,
          family_name: familyNameCell?.textContent?.trim() || "",
          family_productprefix: familyPrefixCell?.textContent?.trim() || "",
          family_commalist: familyCommaListCell?.textContent?.trim() || ""
        };
        families.push(familyData);
      });
      return families;
    }
    /**
     * Process product generation
     */
    async processProductGeneration() {
      const taxRateSelect = document.getElementById("tax_rate_id");
      const unitSelect = document.getElementById("unit_id");
      const processBtn = document.getElementById("process-generate-products");
      if (!taxRateSelect?.value || !unitSelect?.value) {
        alert("Please select both tax rate and unit.");
        return;
      }
      const selectedFamilies = this.getFamilyDataFromCheckedBoxes();
      if (selectedFamilies.length === 0) {
        alert("No families selected.");
        return;
      }
      const originalText = processBtn.innerHTML;
      processBtn.innerHTML = '<i class="fa fa-spin fa-spinner"></i> Generating...';
      processBtn.disabled = true;
      try {
        const familyIds = selectedFamilies.map((f) => f.family_id);
        const csrfToken = document.querySelector('input[name="_csrf"]')?.value || "";
        const payload = {
          family_ids: familyIds,
          tax_rate_id: taxRateSelect.value,
          unit_id: unitSelect.value,
          _csrf: csrfToken
        };
        const response = await fetch(`${location.origin}/invoice/family/generate_products`, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          credentials: "same-origin",
          body: new URLSearchParams(payload).toString()
        });
        const data = await response.json();
        if (data.success) {
          processBtn.innerHTML = '<i class="fa fa-check"></i> Success!';
          alert(data.message || `Successfully generated ${data.count || 0} products!`);
          if (data.warnings && data.warnings.length > 0) {
            console.warn("Warnings during product generation:", data.warnings);
          }
          const modal = document.getElementById("generate-products-modal");
          if (modal && typeof window.bootstrap?.Modal !== "undefined") {
            const bsModal = window.bootstrap.Modal.getInstance(modal);
            if (bsModal) {
              bsModal.hide();
            }
          }
          setTimeout(() => {
            window.location.reload();
          }, 1e3);
        } else {
          processBtn.innerHTML = originalText;
          processBtn.disabled = false;
          alert(`Error: ${data.message || "Unknown error occurred"}`);
        }
      } catch (error) {
        console.error("Product generation error:", error);
        processBtn.innerHTML = originalText;
        processBtn.disabled = false;
        alert("An error occurred while generating products. Please try again.");
      }
    }
  };

  // src/typescript/settings.ts
  var SettingsHandler = class {
    originalDisplayStyles = {};
    originalDisabledStates = {};
    constructor() {
      this.bindEventListeners();
    }
    bindEventListeners() {
      if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", this.initialize.bind(this));
      } else {
        this.initialize();
      }
    }
    initialize() {
      this.toggleSmtpSettings();
      const emailSendMethodEl = document.getElementById("email_send_method");
      if (emailSendMethodEl) {
        emailSendMethodEl.addEventListener("change", this.toggleSmtpSettings.bind(this));
      }
      const fphBtn = document.getElementById("btn_fph_generate");
      if (fphBtn) {
        fphBtn.addEventListener("click", (e) => {
          e.preventDefault();
          this.handleFphGenerateClick();
        });
      }
      const settingsForm = document.getElementById("form-settings");
      const submitBtn = document.getElementById("btn-submit");
      if (submitBtn && settingsForm) {
        if (settingsForm.contains(submitBtn)) {
          submitBtn.addEventListener("click", (e) => {
            e.preventDefault();
            this.handleSettingsSubmitClick();
          });
        }
      }
      const onlineSelect = document.getElementById("online-payment-select");
      if (onlineSelect) {
        onlineSelect.addEventListener(
          "change",
          this.handleOnlinePaymentSelectChange.bind(this)
        );
      }
      this.handleOnlinePaymentSelectChange();
    }
    /**
     * Toggle visibility of SMTP settings based on email_send_method value
     */
    toggleSmtpSettings() {
      const emailSendMethodEl = document.getElementById("email_send_method");
      const div = document.getElementById("div-smtp-settings");
      if (!div || !emailSendMethodEl) return;
      if (emailSendMethodEl.value === "smtp") {
        div.style.display = "";
      } else {
        div.style.display = "none";
      }
    }
    /**
     * Generate fingerprint / client metrics for FPH
     */
    async handleFphGenerateClick() {
      const url = `${location.origin}/invoice/setting/fphgenerate`;
      const requestData = {
        userAgent: navigator.userAgent,
        width: window.screen.width,
        height: window.screen.height,
        scalingFactor: Math.round(window.devicePixelRatio * 100) / 100,
        colourDepth: window.screen.colorDepth,
        windowInnerWidth: window.innerWidth,
        windowInnerHeight: window.innerHeight
      };
      const params = new URLSearchParams();
      Object.entries(requestData).forEach(([key, value]) => {
        params.append(key, value.toString());
      });
      try {
        const response = await fetch(`${url}?${params.toString()}`, {
          method: "GET",
          credentials: "same-origin",
          cache: "no-store",
          headers: { Accept: "application/json" }
        });
        if (!response.ok) {
          throw new Error(`Network response not ok: ${response.status}`);
        }
        const data = await response.json().catch(() => ({}));
        const parsedResponse = parsedata(data);
        if (parsedResponse.success === 1) {
          this.updateSettingField(
            "settings[fph_client_browser_js_user_agent]",
            parsedResponse.userAgent
          );
          this.updateSettingField("settings[fph_client_device_id]", parsedResponse.deviceId);
          this.updateSettingField("settings[fph_screen_width]", parsedResponse.width);
          this.updateSettingField("settings[fph_screen_height]", parsedResponse.height);
          this.updateSettingField(
            "settings[fph_screen_scaling_factor]",
            parsedResponse.scalingFactor
          );
          this.updateSettingField(
            "settings[fph_screen_colour_depth]",
            parsedResponse.colourDepth
          );
          this.updateSettingField("settings[fph_timestamp]", parsedResponse.timestamp);
          this.updateSettingField("settings[fph_window_size]", parsedResponse.windowSize);
          this.updateSettingField(
            "settings[fph_gov_client_user_id]",
            parsedResponse.userUuid
          );
        }
      } catch (error) {
        console.error("FPH generate failed", error);
      }
    }
    /**
     * Helper to update a settings field value
     */
    updateSettingField(fieldId, value) {
      const element = document.getElementById(fieldId);
      if (element && value !== void 0) {
        element.value = value;
      }
    }
    /**
     * Submit settings form - ensure all tab elements are included
     */
    handleSettingsSubmitClick() {
      const form = document.getElementById("form-settings");
      if (!form) return;
      const tabPanes = form.querySelectorAll(".tab-pane");
      this.originalDisplayStyles = {};
      this.originalDisabledStates = {};
      Array.from(tabPanes).toReversed().forEach((pane) => {
        if (pane.id) {
          this.originalDisplayStyles[pane.id] = pane.style.display;
          pane.style.display = "block";
          const disabledElements = pane.querySelectorAll(
            "input:disabled, select:disabled, textarea:disabled"
          );
          disabledElements.forEach((element, index) => {
            const key = `${pane.id}_${index}`;
            this.originalDisabledStates[key] = { element, disabled: true };
            element.disabled = false;
          });
        }
      });
      setTimeout(() => {
        form.submit();
        this.restoreFormState(tabPanes);
      }, 10);
    }
    /**
     * Restore form state after submission
     */
    restoreFormState(tabPanes) {
      tabPanes.forEach((pane) => {
        if (pane.id && this.originalDisplayStyles[pane.id] !== void 0) {
          pane.style.display = this.originalDisplayStyles[pane.id];
        }
      });
      Object.entries(this.originalDisabledStates).forEach(([_, state]) => {
        if (state.element && state.disabled) {
          state.element.disabled = true;
        }
      });
    }
    /**
     * Online payment select change handler (show/hide gateway settings)
     */
    handleOnlinePaymentSelectChange() {
      const select = document.getElementById("online-payment-select");
      if (!select) return;
      const driver = select.value;
      const gatewaySettings = document.querySelectorAll(
        ".gateway-settings"
      );
      gatewaySettings.forEach((element) => {
        if (!element.classList.contains("active-gateway")) {
          element.classList.add("hidden");
        }
      });
      const target = document.getElementById(`gateway-settings-${driver}`);
      if (target) {
        target.classList.remove("hidden");
        target.classList.add("active-gateway");
      }
    }
  };

  // src/typescript/scripts.ts
  function initTooltips() {
    const bootstrap2 = window.bootstrap;
    if (typeof bootstrap2 === "undefined" || !bootstrap2.Tooltip) return;
    const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipElements.forEach((el) => {
      try {
        new bootstrap2.Tooltip(el);
      } catch (e) {
      }
    });
  }
  function initSimpleSelects(root) {
    const TomSelect = window.TomSelect;
    if (typeof TomSelect === "undefined") return;
    const container = root || document;
    const selectElements = container.querySelectorAll(".simple-select");
    selectElements.forEach((el) => {
      if (!el._tomselect) {
        new TomSelect(el, {});
        el._tomselect = true;
      }
    });
  }
  function showFullpageLoader() {
    const loader = document.getElementById("fullpage-loader");
    const loaderError = document.getElementById("loader-error");
    const loaderIcon = document.getElementById("loader-icon");
    if (loader) loader.style.display = "block";
    if (loaderError) loaderError.style.display = "none";
    if (loaderIcon) {
      loaderIcon.classList.add("fa-spin");
      loaderIcon.classList.remove("text-danger");
    }
    setTimeout(() => {
      if (loaderError) loaderError.style.display = "block";
      if (loaderIcon) {
        loaderIcon.classList.remove("fa-spin");
        loaderIcon.classList.add("text-danger");
      }
    }, 1e4);
  }
  function hideFullpageLoader() {
    const loader = document.getElementById("fullpage-loader");
    const loaderError = document.getElementById("loader-error");
    const loaderIcon = document.getElementById("loader-icon");
    if (loader) loader.style.display = "none";
    if (loaderError) loaderError.style.display = "none";
    if (loaderIcon) {
      loaderIcon.classList.add("fa-spin");
      loaderIcon.classList.remove("text-danger");
    }
  }
  function initPasswordMeter() {
    const passwordInput = document.querySelector(".passwordmeter-input");
    if (!passwordInput) return;
    passwordInput.addEventListener("input", () => {
      const password = passwordInput.value;
      const strength = calculatePasswordStrength(password);
      const meter2 = document.querySelector(".passmeter-2");
      const meter3 = document.querySelector(".passmeter-3");
      if (meter2 && meter3) {
        meter2.style.display = "none";
        meter3.style.display = "none";
        if (strength >= 4) {
          meter2.style.display = "block";
          meter3.style.display = "block";
        } else if (strength >= 3) {
          meter2.style.display = "block";
        }
      }
    });
  }
  function calculatePasswordStrength(password) {
    let strength = 0;
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    return strength;
  }
  function initializeScripts() {
    document.addEventListener("DOMContentLoaded", () => {
      initTooltips();
      initSimpleSelects();
      initPasswordMeter();
      document.addEventListener("click", (e) => {
        const target = e.target;
        if (target.classList.contains("ajax-loader")) {
          showFullpageLoader();
        }
        if (target.classList.contains("fullpage-loader-close")) {
          hideFullpageLoader();
        }
      });
    });
  }
  initializeScripts();

  // src/typescript/family-commalist-picker.ts
  var FamilyCommalistPicker = class {
    container;
    textarea;
    selectedNumbers = /* @__PURE__ */ new Set();
    currentPage = 1;
    numbersPerPage = 50;
    totalPages = 4;
    // 200 numbers / 50 per page
    constructor(containerId, textareaId) {
      this.container = document.getElementById(containerId);
      this.textarea = document.getElementById(textareaId);
      if (!this.container || !this.textarea) {
        throw new Error("Required elements not found");
      }
      this.parseInitialValue();
      this.render();
      this.attachEventListeners();
    }
    parseInitialValue() {
      if (this.textarea.value) {
        const existingNumbers = this.textarea.value.split(",").map((n) => parseInt(n.trim())).filter((n) => !isNaN(n) && n >= 1 && n <= 200);
        this.selectedNumbers = new Set(existingNumbers);
      }
    }
    render() {
      this.container.innerHTML = this.getHTML();
    }
    getHTML() {
      const paginatedNumbers = this.getPaginatedNumbers();
      const selectedCount = this.selectedNumbers.size;
      const pageInfo = this.getPageInfo();
      return `
            <div class="family-commalist-picker">
                <div class="picker-header mb-3">
                    <h5 class="mb-2">
                        <i class="bi bi-list-ol"></i> 
                        Select Numbers (1-200)
                    </h5>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="selected-info">
                            <span class="badge bg-primary me-2">
                                ${selectedCount} selected
                            </span>
                            <span class="text-muted small">${pageInfo}</span>
                        </div>
                        
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-success" onclick="picker.selectPage()" title="Select all numbers on current page">
                                <i class="bi bi-check-square"></i> Page
                            </button>
                            <button type="button" class="btn btn-outline-warning" onclick="picker.deselectPage()" title="Deselect all numbers on current page">
                                <i class="bi bi-square"></i> Page
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="picker.clearAll()" title="Clear all selections">
                                <i class="bi bi-trash"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quick Range Selection -->
                <div class="quick-ranges mb-3">
                    <small class="text-muted d-block mb-2">Quick ranges:</small>
                    <div class="btn-group btn-group-sm flex-wrap" role="group">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="picker.selectRange(1, 10)" title="Select 1-10">1-10</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="picker.selectRange(11, 25)" title="Select 11-25">11-25</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="picker.selectRange(26, 50)" title="Select 26-50">26-50</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="picker.selectRange(51, 100)" title="Select 51-100">51-100</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="picker.selectRange(101, 150)" title="Select 101-150">101-150</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="picker.selectRange(151, 200)" title="Select 151-200">151-200</button>
                    </div>
                </div>

                <!-- Number Grid -->
                <div class="numbers-grid mb-3">
                    <div class="number-buttons">
                        ${paginatedNumbers.map((num) => `
                            <button type="button" 
                                    class="btn number-btn ${this.selectedNumbers.has(num) ? "btn-success" : "btn-outline-secondary"}"
                                    onclick="picker.toggleNumber(${num})"
                                    title="Toggle number ${num}">
                                ${num}
                            </button>
                        `).join("")}
                    </div>
                </div>

                <!-- Pagination -->
                <div class="pagination-controls d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="picker.prevPage()" ${this.currentPage === 1 ? "disabled" : ""}>
                        <i class="bi bi-chevron-left"></i> Previous
                    </button>
                    
                    <div class="page-buttons">
                        ${[1, 2, 3, 4].map((page) => `
                            <button type="button" 
                                    class="btn btn-sm me-1 ${page === this.currentPage ? "btn-primary" : "btn-outline-primary"}"
                                    onclick="picker.goToPage(${page})">
                                ${page}
                            </button>
                        `).join("")}
                    </div>
                    
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="picker.nextPage()" ${this.currentPage === this.totalPages ? "disabled" : ""}>
                        Next <i class="bi bi-chevron-right"></i>
                    </button>
                </div>

                ${selectedCount > 0 ? `
                    <div class="selected-preview mt-3">
                        <small class="text-muted d-block mb-1">Selected numbers:</small>
                        <div class="selected-numbers-display">
                            <span class="text-muted small">${Array.from(this.selectedNumbers).sort((a, b) => a - b).join(", ")}</span>
                        </div>
                    </div>
                ` : ""}
            </div>
        `;
    }
    getPaginatedNumbers() {
      const startIndex = (this.currentPage - 1) * this.numbersPerPage;
      const endIndex = startIndex + this.numbersPerPage;
      const allNumbers = Array.from({ length: 200 }, (_, i) => i + 1);
      return allNumbers.slice(startIndex, endIndex);
    }
    getPageInfo() {
      const start = (this.currentPage - 1) * this.numbersPerPage + 1;
      const end = Math.min(this.currentPage * this.numbersPerPage, 200);
      return `${start}-${end} of 200`;
    }
    toggleNumber(num) {
      if (this.selectedNumbers.has(num)) {
        this.selectedNumbers.delete(num);
      } else {
        this.selectedNumbers.add(num);
      }
      this.updateTextarea();
      this.render();
    }
    clearAll() {
      this.selectedNumbers.clear();
      this.updateTextarea();
      this.render();
    }
    selectRange(start, end) {
      for (let i = start; i <= end; i++) {
        if (i >= 1 && i <= 200) {
          this.selectedNumbers.add(i);
        }
      }
      this.updateTextarea();
      this.render();
    }
    selectPage() {
      this.getPaginatedNumbers().forEach((num) => {
        this.selectedNumbers.add(num);
      });
      this.updateTextarea();
      this.render();
    }
    deselectPage() {
      this.getPaginatedNumbers().forEach((num) => {
        this.selectedNumbers.delete(num);
      });
      this.updateTextarea();
      this.render();
    }
    goToPage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page;
        this.render();
      }
    }
    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++;
        this.render();
      }
    }
    prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--;
        this.render();
      }
    }
    updateTextarea() {
      const sortedNumbers = Array.from(this.selectedNumbers).sort((a, b) => a - b);
      const commalistValue = sortedNumbers.join(", ");
      this.textarea.value = commalistValue;
      this.textarea.dispatchEvent(new Event("change", { bubbles: true }));
      this.textarea.dispatchEvent(new Event("input", { bubbles: true }));
    }
    attachEventListeners() {
      this.textarea.addEventListener("input", () => {
        this.parseInitialValue();
        this.render();
      });
    }
  };
  var picker = null;
  function initializeCommalistPicker() {
    window.toggleCommalistPicker = toggleCommalistPicker;
    window.picker = null;
  }
  function toggleCommalistPicker() {
    const container = document.getElementById("commalist-picker-container");
    const button = document.getElementById("toggle-picker-btn");
    if (!container || !button) return;
    if (container.style.display === "none") {
      container.style.display = "block";
      button.innerHTML = '<i class="bi bi-grid-3x3-gap-fill"></i> Hide Number Picker';
      if (!picker) {
        const pickerDiv = document.createElement("div");
        pickerDiv.id = "number-picker";
        const infoAlert = container.querySelector(".alert");
        if (infoAlert && infoAlert.nextSibling) {
          container.insertBefore(pickerDiv, infoAlert.nextSibling);
        } else {
          container.appendChild(pickerDiv);
        }
        picker = new FamilyCommalistPicker("number-picker", "family_commalist");
        window.picker = picker;
      }
    } else {
      container.style.display = "none";
      button.innerHTML = '<i class="bi bi-grid-3x3-gap"></i> Show Number Picker';
    }
  }
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeCommalistPicker);
  } else {
    initializeCommalistPicker();
  }

  // src/typescript/index.ts
  console.log("Invoice TypeScript bundle loaded");
  var InvoiceApp = class {
    #createCreditHandler;
    #quoteHandler;
    #clientHandler;
    #invoiceHandler;
    #productHandler;
    #taskHandler;
    #salesOrderHandler;
    #familyHandler;
    #settingsHandler;
    constructor() {
      this.#createCreditHandler = new CreateCreditHandler();
      this.#quoteHandler = new QuoteHandler();
      this.#clientHandler = new ClientHandler();
      this.#invoiceHandler = new InvoiceHandler();
      this.#productHandler = new ProductHandler();
      this.#taskHandler = new TaskHandler();
      this.#salesOrderHandler = new SalesOrderHandler();
      this.#familyHandler = new FamilyHandler();
      this.#settingsHandler = new SettingsHandler();
      this.initializeTooltips();
      this.initializeTaggableFocus();
      initTooltips();
      initSimpleSelects();
      initPasswordMeter();
      this.initializeFullpageLoader();
      console.log(
        "Invoice TypeScript App initialized with all core handlers: Quote, Client, Invoice, Product, Task, SalesOrder, Family, and Settings"
      );
    }
    /**
     * Initialize Bootstrap tooltips
     */
    initializeTooltips() {
      document.addEventListener("DOMContentLoaded", () => {
        if (typeof bootstrap !== "undefined" && bootstrap.Tooltip) {
          const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
          tooltipElements.forEach((element) => {
            try {
              new bootstrap.Tooltip(element);
            } catch (error) {
              console.warn("Tooltip initialization failed:", error);
            }
          });
        }
      });
    }
    /**
     * Keep track of last taggable focused element
     */
    initializeTaggableFocus() {
      document.addEventListener(
        "focus",
        (event) => {
          const target = event.target;
          if (target?.classList?.contains("taggable")) {
            window.lastTaggableClicked = target;
          }
        },
        true
      );
    }
    /**
     * Initialize fullpage loader functionality
     */
    initializeFullpageLoader() {
      document.addEventListener("click", (e) => {
        const target = e.target;
        if (target.classList.contains("ajax-loader")) {
          showFullpageLoader();
        }
        if (target.classList.contains("fullpage-loader-close")) {
          hideFullpageLoader();
        }
      });
    }
  };
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => new InvoiceApp());
  } else {
    new InvoiceApp();
  }
  return __toCommonJS(index_exports);
})();
//# sourceMappingURL=invoice-typescript-iife.js.map
