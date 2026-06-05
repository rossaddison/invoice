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
    InvoiceApp: () => InvoiceApp,
    initInvIndex: () => initInvIndex,
    initQuoteIndex: () => initQuoteIndex
  });

  // node_modules/htmx.org/dist/htmx.esm.js
  var htmx2 = (function() {
    "use strict";
    const htmx = {
      // Tsc madness here, assigning the functions directly results in an invalid TypeScript output, but reassigning is fine
      /* Event processing */
      /** @type {typeof onLoadHelper} */
      onLoad: null,
      /** @type {typeof processNode} */
      process: null,
      /** @type {typeof addEventListenerImpl} */
      on: null,
      /** @type {typeof removeEventListenerImpl} */
      off: null,
      /** @type {typeof triggerEvent} */
      trigger: null,
      /** @type {typeof ajaxHelper} */
      ajax: null,
      /* DOM querying helpers */
      /** @type {typeof find} */
      find: null,
      /** @type {typeof findAll} */
      findAll: null,
      /** @type {typeof closest} */
      closest: null,
      /**
       * Returns the input values that would resolve for a given element via the htmx value resolution mechanism
       *
       * @see https://htmx.org/api/#values
       *
       * @param {Element} elt the element to resolve values on
       * @param {HttpVerb} type the request type (e.g. **get** or **post**) non-GET's will include the enclosing form of the element. Defaults to **post**
       * @returns {Object}
       */
      values: function(elt, type) {
        const inputValues = getInputValues(elt, type || "post");
        return inputValues.values;
      },
      /* DOM manipulation helpers */
      /** @type {typeof removeElement} */
      remove: null,
      /** @type {typeof addClassToElement} */
      addClass: null,
      /** @type {typeof removeClassFromElement} */
      removeClass: null,
      /** @type {typeof toggleClassOnElement} */
      toggleClass: null,
      /** @type {typeof takeClassForElement} */
      takeClass: null,
      /** @type {typeof swap} */
      swap: null,
      /* Extension entrypoints */
      /** @type {typeof defineExtension} */
      defineExtension: null,
      /** @type {typeof removeExtension} */
      removeExtension: null,
      /* Debugging */
      /** @type {typeof logAll} */
      logAll: null,
      /** @type {typeof logNone} */
      logNone: null,
      /* Debugging */
      /**
       * The logger htmx uses to log with
       *
       * @see https://htmx.org/api/#logger
       */
      logger: null,
      /**
       * A property holding the configuration htmx uses at runtime.
       *
       * Note that using a [meta tag](https://htmx.org/docs/#config) is the preferred mechanism for setting these properties.
       *
       * @see https://htmx.org/api/#config
       */
      config: {
        /**
         * Whether to use history.
         * @type boolean
         * @default true
         */
        historyEnabled: true,
        /**
         * The number of pages to keep in **sessionStorage** for history support.
         * @type number
         * @default 10
         */
        historyCacheSize: 10,
        /**
         * @type boolean
         * @default false
         */
        refreshOnHistoryMiss: false,
        /**
         * The default swap style to use if **[hx-swap](https://htmx.org/attributes/hx-swap)** is omitted.
         * @type HtmxSwapStyle
         * @default 'innerHTML'
         */
        defaultSwapStyle: "innerHTML",
        /**
         * The default delay between receiving a response from the server and doing the swap.
         * @type number
         * @default 0
         */
        defaultSwapDelay: 0,
        /**
         * The default delay between completing the content swap and settling attributes.
         * @type number
         * @default 20
         */
        defaultSettleDelay: 20,
        /**
         * If true, htmx will inject a small amount of CSS into the page to make indicators invisible unless the **htmx-indicator** class is present.
         * @type boolean
         * @default true
         */
        includeIndicatorStyles: true,
        /**
         * The class to place on indicators when a request is in flight.
         * @type string
         * @default 'htmx-indicator'
         */
        indicatorClass: "htmx-indicator",
        /**
         * The class to place on triggering elements when a request is in flight.
         * @type string
         * @default 'htmx-request'
         */
        requestClass: "htmx-request",
        /**
         * The class to temporarily place on elements that htmx has added to the DOM.
         * @type string
         * @default 'htmx-added'
         */
        addedClass: "htmx-added",
        /**
         * The class to place on target elements when htmx is in the settling phase.
         * @type string
         * @default 'htmx-settling'
         */
        settlingClass: "htmx-settling",
        /**
         * The class to place on target elements when htmx is in the swapping phase.
         * @type string
         * @default 'htmx-swapping'
         */
        swappingClass: "htmx-swapping",
        /**
         * Allows the use of eval-like functionality in htmx, to enable **hx-vars**, trigger conditions & script tag evaluation. Can be set to **false** for CSP compatibility.
         * @type boolean
         * @default true
         */
        allowEval: true,
        /**
         * If set to false, disables the interpretation of script tags.
         * @type boolean
         * @default true
         */
        allowScriptTags: true,
        /**
         * If set, the nonce will be added to inline scripts.
         * @type string
         * @default ''
         */
        inlineScriptNonce: "",
        /**
         * If set, the nonce will be added to inline styles.
         * @type string
         * @default ''
         */
        inlineStyleNonce: "",
        /**
         * The attributes to settle during the settling phase.
         * @type string[]
         * @default ['class', 'style', 'width', 'height']
         */
        attributesToSettle: ["class", "style", "width", "height"],
        /**
         * Allow cross-site Access-Control requests using credentials such as cookies, authorization headers or TLS client certificates.
         * @type boolean
         * @default false
         */
        withCredentials: false,
        /**
         * @type number
         * @default 0
         */
        timeout: 0,
        /**
         * The default implementation of **getWebSocketReconnectDelay** for reconnecting after unexpected connection loss by the event code **Abnormal Closure**, **Service Restart** or **Try Again Later**.
         * @type {'full-jitter' | ((retryCount:number) => number)}
         * @default "full-jitter"
         */
        wsReconnectDelay: "full-jitter",
        /**
         * The type of binary data being received over the WebSocket connection
         * @type BinaryType
         * @default 'blob'
         */
        wsBinaryType: "blob",
        /**
         * @type string
         * @default '[hx-disable], [data-hx-disable]'
         */
        disableSelector: "[hx-disable], [data-hx-disable]",
        /**
         * @type {'auto' | 'instant' | 'smooth'}
         * @default 'instant'
         */
        scrollBehavior: "instant",
        /**
         * If the focused element should be scrolled into view.
         * @type boolean
         * @default false
         */
        defaultFocusScroll: false,
        /**
         * If set to true htmx will include a cache-busting parameter in GET requests to avoid caching partial responses by the browser
         * @type boolean
         * @default false
         */
        getCacheBusterParam: false,
        /**
         * If set to true, htmx will use the View Transition API when swapping in new content.
         * @type boolean
         * @default false
         */
        globalViewTransitions: false,
        /**
         * htmx will format requests with these methods by encoding their parameters in the URL, not the request body
         * @type {(HttpVerb)[]}
         * @default ['get', 'delete']
         */
        methodsThatUseUrlParams: ["get", "delete"],
        /**
         * If set to true, disables htmx-based requests to non-origin hosts.
         * @type boolean
         * @default false
         */
        selfRequestsOnly: true,
        /**
         * If set to true htmx will not update the title of the document when a title tag is found in new content
         * @type boolean
         * @default false
         */
        ignoreTitle: false,
        /**
         * Whether the target of a boosted element is scrolled into the viewport.
         * @type boolean
         * @default true
         */
        scrollIntoViewOnBoost: true,
        /**
         * The cache to store evaluated trigger specifications into.
         * You may define a simple object to use a never-clearing cache, or implement your own system using a [proxy object](https://developer.mozilla.org/docs/Web/JavaScript/Reference/Global_Objects/Proxy)
         * @type {Object|null}
         * @default null
         */
        triggerSpecsCache: null,
        /** @type boolean */
        disableInheritance: false,
        /** @type HtmxResponseHandlingConfig[] */
        responseHandling: [
          { code: "204", swap: false },
          { code: "[23]..", swap: true },
          { code: "[45]..", swap: false, error: true }
        ],
        /**
         * Whether to process OOB swaps on elements that are nested within the main response element.
         * @type boolean
         * @default true
         */
        allowNestedOobSwaps: true,
        /**
         * Whether to treat history cache miss full page reload requests as a "HX-Request" by returning this response header
         * This should always be disabled when using HX-Request header to optionally return partial responses
         * @type boolean
         * @default true
         */
        historyRestoreAsHxRequest: true,
        /**
         * Whether to report input validation errors to the end user and update focus to the first input that fails validation.
         * This should always be enabled as this matches default browser form submit behaviour
         * @type boolean
         * @default false
         */
        reportValidityOfForms: false
      },
      /** @type {typeof parseInterval} */
      parseInterval: null,
      /**
       * proxy of window.location used for page reload functions
       * @type location
       */
      location,
      /** @type {typeof internalEval} */
      _: null,
      version: "2.0.10"
    };
    htmx.onLoad = onLoadHelper;
    htmx.process = processNode;
    htmx.on = addEventListenerImpl;
    htmx.off = removeEventListenerImpl;
    htmx.trigger = triggerEvent;
    htmx.ajax = ajaxHelper;
    htmx.find = find;
    htmx.findAll = findAll;
    htmx.closest = closest;
    htmx.remove = removeElement;
    htmx.addClass = addClassToElement;
    htmx.removeClass = removeClassFromElement;
    htmx.toggleClass = toggleClassOnElement;
    htmx.takeClass = takeClassForElement;
    htmx.swap = swap;
    htmx.defineExtension = defineExtension;
    htmx.removeExtension = removeExtension;
    htmx.logAll = logAll;
    htmx.logNone = logNone;
    htmx.parseInterval = parseInterval;
    htmx._ = internalEval;
    const internalAPI = {
      addTriggerHandler,
      bodyContains,
      canAccessLocalStorage,
      findThisElement,
      filterValues,
      swap,
      hasAttribute,
      getAttributeValue,
      getClosestAttributeValue,
      getClosestMatch,
      getExpressionVars,
      getHeaders,
      getInputValues,
      getInternalData,
      getSwapSpecification,
      getTriggerSpecs,
      getTarget,
      makeFragment,
      mergeObjects,
      makeSettleInfo,
      oobSwap,
      querySelectorExt,
      settleImmediately,
      shouldCancel,
      triggerEvent,
      triggerErrorEvent,
      withExtensions
    };
    const VERBS = ["get", "post", "put", "delete", "patch"];
    const VERB_SELECTOR = VERBS.map(function(verb) {
      return "[hx-" + verb + "], [data-hx-" + verb + "]";
    }).join(", ");
    function parseInterval(str2) {
      if (str2 == void 0) {
        return void 0;
      }
      let interval = NaN;
      if (str2.slice(-2) == "ms") {
        interval = parseFloat(str2.slice(0, -2));
      } else if (str2.slice(-1) == "s") {
        interval = parseFloat(str2.slice(0, -1)) * 1e3;
      } else if (str2.slice(-1) == "m") {
        interval = parseFloat(str2.slice(0, -1)) * 1e3 * 60;
      } else {
        interval = parseFloat(str2);
      }
      return isNaN(interval) ? void 0 : interval;
    }
    function getRawAttribute(elt, name) {
      return elt instanceof Element && elt.getAttribute(name);
    }
    function hasAttribute(elt, qualifiedName) {
      return !!elt.hasAttribute && (elt.hasAttribute(qualifiedName) || elt.hasAttribute("data-" + qualifiedName));
    }
    function getAttributeValue(elt, qualifiedName) {
      return getRawAttribute(elt, qualifiedName) || getRawAttribute(elt, "data-" + qualifiedName);
    }
    function parentElt(elt) {
      const parent = elt.parentElement;
      if (!parent && elt.parentNode instanceof ShadowRoot) return elt.parentNode;
      return parent;
    }
    function getDocument() {
      return document;
    }
    function getRootNode(elt, global) {
      return elt.getRootNode ? elt.getRootNode({ composed: global }) : getDocument();
    }
    function getClosestMatch(elt, condition) {
      while (elt && !condition(elt)) {
        elt = parentElt(elt);
      }
      return elt || null;
    }
    function getAttributeValueWithDisinheritance(initialElement, ancestor, attributeName) {
      const attributeValue = getAttributeValue(ancestor, attributeName);
      const disinherit = getAttributeValue(ancestor, "hx-disinherit");
      var inherit = getAttributeValue(ancestor, "hx-inherit");
      if (initialElement !== ancestor) {
        if (htmx.config.disableInheritance) {
          if (inherit && (inherit === "*" || inherit.split(" ").indexOf(attributeName) >= 0)) {
            return attributeValue;
          } else {
            return null;
          }
        }
        if (disinherit && (disinherit === "*" || disinherit.split(" ").indexOf(attributeName) >= 0)) {
          return "unset";
        }
      }
      return attributeValue;
    }
    function getClosestAttributeValue(elt, attributeName) {
      let closestAttr = null;
      getClosestMatch(elt, function(e) {
        return !!(closestAttr = getAttributeValueWithDisinheritance(elt, asElement(e), attributeName));
      });
      if (closestAttr !== "unset") {
        return closestAttr;
      }
    }
    function matches(elt, selector) {
      return elt instanceof Element && elt.matches(selector);
    }
    function getStartTag(str2) {
      const tagMatcher = /<([a-z][^\/\0>\x20\t\r\n\f]*)/i;
      const match = tagMatcher.exec(str2);
      if (match) {
        return match[1].toLowerCase();
      } else {
        return "";
      }
    }
    function parseHTML(resp) {
      if ("parseHTMLUnsafe" in Document) {
        return Document.parseHTMLUnsafe(resp);
      }
      const parser = new DOMParser();
      return parser.parseFromString(resp, "text/html");
    }
    function takeChildrenFor(fragment, elt) {
      while (elt.childNodes.length > 0) {
        fragment.append(elt.childNodes[0]);
      }
    }
    function duplicateScript(script) {
      const newScript = getDocument().createElement("script");
      forEach(script.attributes, function(attr) {
        newScript.setAttribute(attr.name, attr.value);
      });
      newScript.textContent = script.textContent;
      newScript.async = false;
      if (htmx.config.inlineScriptNonce) {
        newScript.nonce = htmx.config.inlineScriptNonce;
      }
      return newScript;
    }
    function isJavaScriptScriptNode(script) {
      return script.matches("script") && (script.type === "text/javascript" || script.type === "module" || script.type === "");
    }
    function normalizeScriptTags(fragment) {
      Array.from(fragment.querySelectorAll("script")).forEach(
        /** @param {HTMLScriptElement} script */
        (script) => {
          if (isJavaScriptScriptNode(script)) {
            const newScript = duplicateScript(script);
            const parent = script.parentNode;
            try {
              parent.insertBefore(newScript, script);
            } catch (e) {
              logError(e);
            } finally {
              script.remove();
            }
          }
        }
      );
    }
    function makeFragment(response) {
      const responseWithNoHead = response.replace(/<head(\s[^>]*)?>[\s\S]*?<\/head>/i, "");
      const startTag = getStartTag(responseWithNoHead);
      let fragment;
      if (startTag === "html") {
        fragment = /** @type DocumentFragmentWithTitle */
        new DocumentFragment();
        const doc = parseHTML(response);
        takeChildrenFor(fragment, doc.body);
        fragment.title = doc.title;
      } else if (startTag === "body") {
        fragment = /** @type DocumentFragmentWithTitle */
        new DocumentFragment();
        const doc = parseHTML(responseWithNoHead);
        takeChildrenFor(fragment, doc.body);
        fragment.title = doc.title;
      } else {
        const doc = parseHTML('<body><template class="internal-htmx-wrapper">' + responseWithNoHead + "</template></body>");
        fragment = /** @type DocumentFragmentWithTitle */
        doc.querySelector("template").content;
        fragment.title = doc.title;
        var titleElement = fragment.querySelector("title");
        if (titleElement && titleElement.parentNode === fragment) {
          titleElement.remove();
          fragment.title = titleElement.innerText;
        }
      }
      if (fragment) {
        if (htmx.config.allowScriptTags) {
          normalizeScriptTags(fragment);
        } else {
          fragment.querySelectorAll("script").forEach((script) => script.remove());
        }
      }
      return fragment;
    }
    function maybeCall(func) {
      if (func) {
        func();
      }
    }
    function isType(o, type) {
      return Object.prototype.toString.call(o) === "[object " + type + "]";
    }
    function isFunction(o) {
      return typeof o === "function";
    }
    function isRawObject(o) {
      return isType(o, "Object");
    }
    function getInternalData(elt) {
      const dataProp = "htmx-internal-data";
      let data = elt[dataProp];
      if (!data) {
        data = elt[dataProp] = {};
      }
      return data;
    }
    function toArray(arr) {
      const returnArr = [];
      if (arr) {
        for (let i = 0; i < arr.length; i++) {
          returnArr.push(arr[i]);
        }
      }
      return returnArr;
    }
    function forEach(arr, func) {
      if (arr) {
        for (let i = 0; i < arr.length; i++) {
          func(arr[i]);
        }
      }
    }
    function isScrolledIntoView(el) {
      const rect = el.getBoundingClientRect();
      const elemTop = rect.top;
      const elemBottom = rect.bottom;
      return elemTop < window.innerHeight && elemBottom >= 0;
    }
    function bodyContains(elt) {
      return elt.getRootNode({ composed: true }) === document;
    }
    function splitOnWhitespace(trigger) {
      return trigger.trim().split(/\s+/);
    }
    function mergeObjects(obj1, obj2) {
      for (const key in obj2) {
        if (obj2.hasOwnProperty(key)) {
          obj1[key] = obj2[key];
        }
      }
      return obj1;
    }
    function parseJSON(jString) {
      try {
        return JSON.parse(jString);
      } catch (error) {
        logError(error);
        return null;
      }
    }
    function canAccessLocalStorage() {
      const test = "htmx:sessionStorageTest";
      try {
        sessionStorage.setItem(test, test);
        sessionStorage.removeItem(test);
        return true;
      } catch (e) {
        return false;
      }
    }
    function normalizePath(path) {
      try {
        const url = new URL(path, window.location.href);
        path = url.pathname + url.search;
      } catch (e) {
      }
      if (path != "/") {
        path = path.replace(/\/+$/, "");
      }
      return path;
    }
    function internalEval(str) {
      return maybeEval(getDocument().body, function() {
        return eval(str);
      });
    }
    function onLoadHelper(callback) {
      const value = htmx.on(
        "htmx:load",
        /** @param {CustomEvent} evt */
        function(evt) {
          callback(evt.detail.elt);
        }
      );
      return value;
    }
    function logAll() {
      htmx.logger = function(elt, event, data) {
        if (console) {
          console.log(event, elt, data);
        }
      };
    }
    function logNone() {
      htmx.logger = null;
    }
    function find(eltOrSelector, selector) {
      if (typeof eltOrSelector !== "string") {
        return eltOrSelector.querySelector(selector);
      } else {
        return find(getDocument(), eltOrSelector);
      }
    }
    function findAll(eltOrSelector, selector) {
      if (typeof eltOrSelector !== "string") {
        return eltOrSelector.querySelectorAll(selector);
      } else {
        return findAll(getDocument(), eltOrSelector);
      }
    }
    function getWindow() {
      return window;
    }
    function removeElement(elt, delay) {
      elt = resolveTarget(elt);
      if (delay) {
        getWindow().setTimeout(function() {
          removeElement(elt);
          elt = null;
        }, delay);
      } else {
        parentElt(elt).removeChild(elt);
      }
    }
    function asElement(elt) {
      return elt instanceof Element ? elt : null;
    }
    function asHtmlElement(elt) {
      return elt instanceof HTMLElement ? elt : null;
    }
    function asString(value) {
      return typeof value === "string" ? value : null;
    }
    function asParentNode(elt) {
      return elt instanceof Element || elt instanceof Document || elt instanceof DocumentFragment ? elt : null;
    }
    function addClassToElement(elt, clazz, delay) {
      elt = asElement(resolveTarget(elt));
      if (!elt) {
        return;
      }
      if (delay) {
        getWindow().setTimeout(function() {
          addClassToElement(elt, clazz);
          elt = null;
        }, delay);
      } else {
        elt.classList && elt.classList.add(clazz);
      }
    }
    function removeClassFromElement(node, clazz, delay) {
      let elt = asElement(resolveTarget(node));
      if (!elt) {
        return;
      }
      if (delay) {
        getWindow().setTimeout(function() {
          removeClassFromElement(elt, clazz);
          elt = null;
        }, delay);
      } else {
        if (elt.classList) {
          elt.classList.remove(clazz);
          if (elt.classList.length === 0) {
            elt.removeAttribute("class");
          }
        }
      }
    }
    function toggleClassOnElement(elt, clazz) {
      elt = resolveTarget(elt);
      elt.classList.toggle(clazz);
    }
    function takeClassForElement(elt, clazz) {
      elt = resolveTarget(elt);
      forEach(elt.parentElement.children, function(child) {
        removeClassFromElement(child, clazz);
      });
      addClassToElement(asElement(elt), clazz);
    }
    function closest(elt, selector) {
      elt = asElement(resolveTarget(elt));
      if (elt) {
        return elt.closest(selector);
      }
      return null;
    }
    function startsWith(str2, prefix) {
      return str2.substring(0, prefix.length) === prefix;
    }
    function endsWith(str2, suffix) {
      return str2.substring(str2.length - suffix.length) === suffix;
    }
    function normalizeSelector(selector) {
      const trimmedSelector = selector.trim();
      if (startsWith(trimmedSelector, "<") && endsWith(trimmedSelector, "/>")) {
        return trimmedSelector.substring(1, trimmedSelector.length - 2);
      } else {
        return trimmedSelector;
      }
    }
    function querySelectorAllExt(elt, selector, global) {
      if (selector.indexOf("global ") === 0) {
        return querySelectorAllExt(elt, selector.slice(7), true);
      }
      elt = resolveTarget(elt);
      const parts = [];
      {
        let chevronsCount = 0;
        let offset = 0;
        for (let i = 0; i < selector.length; i++) {
          const char = selector[i];
          if (char === "," && chevronsCount === 0) {
            parts.push(selector.substring(offset, i));
            offset = i + 1;
            continue;
          }
          if (char === "<") {
            chevronsCount++;
          } else if (char === "/" && i < selector.length - 1 && selector[i + 1] === ">") {
            chevronsCount--;
          }
        }
        if (offset < selector.length) {
          parts.push(selector.substring(offset));
        }
      }
      const result = [];
      const unprocessedParts = [];
      while (parts.length > 0) {
        const selector2 = normalizeSelector(parts.shift());
        let item;
        if (selector2.indexOf("closest ") === 0) {
          item = closest(asElement(elt), normalizeSelector(selector2.slice(8)));
        } else if (selector2.indexOf("find ") === 0) {
          item = find(asParentNode(elt), normalizeSelector(selector2.slice(5)));
        } else if (selector2 === "next" || selector2 === "nextElementSibling") {
          item = asElement(elt).nextElementSibling;
        } else if (selector2.indexOf("next ") === 0) {
          item = scanForwardQuery(elt, normalizeSelector(selector2.slice(5)), !!global);
        } else if (selector2 === "previous" || selector2 === "previousElementSibling") {
          item = asElement(elt).previousElementSibling;
        } else if (selector2.indexOf("previous ") === 0) {
          item = scanBackwardsQuery(elt, normalizeSelector(selector2.slice(9)), !!global);
        } else if (selector2 === "document") {
          item = document;
        } else if (selector2 === "window") {
          item = window;
        } else if (selector2 === "body") {
          item = document.body;
        } else if (selector2 === "root") {
          item = getRootNode(elt, !!global);
        } else if (selector2 === "host") {
          item = /** @type ShadowRoot */
          elt.getRootNode().host;
        } else {
          unprocessedParts.push(selector2);
        }
        if (item) {
          result.push(item);
        }
      }
      if (unprocessedParts.length > 0) {
        const standardSelector = unprocessedParts.join(",");
        const rootNode = asParentNode(getRootNode(elt, !!global));
        result.push(...toArray(rootNode.querySelectorAll(standardSelector)));
      }
      return result;
    }
    var scanForwardQuery = function(start, match, global) {
      const results = asParentNode(getRootNode(start, global)).querySelectorAll(match);
      for (let i = 0; i < results.length; i++) {
        const elt = results[i];
        if (elt.compareDocumentPosition(start) === Node.DOCUMENT_POSITION_PRECEDING) {
          return elt;
        }
      }
    };
    var scanBackwardsQuery = function(start, match, global) {
      const results = asParentNode(getRootNode(start, global)).querySelectorAll(match);
      for (let i = results.length - 1; i >= 0; i--) {
        const elt = results[i];
        if (elt.compareDocumentPosition(start) === Node.DOCUMENT_POSITION_FOLLOWING) {
          return elt;
        }
      }
    };
    function querySelectorExt(eltOrSelector, selector) {
      if (typeof eltOrSelector !== "string") {
        return querySelectorAllExt(eltOrSelector, selector)[0];
      } else {
        return querySelectorAllExt(getDocument().body, eltOrSelector)[0];
      }
    }
    function resolveTarget(eltOrSelector, context) {
      if (typeof eltOrSelector === "string") {
        return find(asParentNode(context) || document, eltOrSelector);
      } else {
        return eltOrSelector;
      }
    }
    function processEventArgs(arg1, arg2, arg3, arg4) {
      if (isFunction(arg2)) {
        return {
          target: getDocument().body,
          event: asString(arg1),
          listener: arg2,
          options: arg3
        };
      } else {
        return {
          target: resolveTarget(arg1),
          event: asString(arg2),
          listener: arg3,
          options: arg4
        };
      }
    }
    function addEventListenerImpl(arg1, arg2, arg3, arg4) {
      ready(function() {
        const eventArgs = processEventArgs(arg1, arg2, arg3, arg4);
        eventArgs.target.addEventListener(eventArgs.event, eventArgs.listener, eventArgs.options);
      });
      const b = isFunction(arg2);
      return b ? arg2 : arg3;
    }
    function removeEventListenerImpl(arg1, arg2, arg3) {
      ready(function() {
        const eventArgs = processEventArgs(arg1, arg2, arg3);
        eventArgs.target.removeEventListener(eventArgs.event, eventArgs.listener);
      });
      return isFunction(arg2) ? arg2 : arg3;
    }
    const DUMMY_ELT = getDocument().createElement("output");
    function findAttributeTargets(elt, attrName) {
      const attrTarget = getClosestAttributeValue(elt, attrName);
      if (attrTarget) {
        if (attrTarget === "this") {
          return [findThisElement(elt, attrName)];
        } else {
          const result = querySelectorAllExt(elt, attrTarget);
          const shouldInherit = /(^|,)(\s*)inherit(\s*)($|,)/.test(attrTarget);
          if (shouldInherit) {
            const eltToInheritFrom = asElement(getClosestMatch(elt, function(parent) {
              return parent !== elt && hasAttribute(asElement(parent), attrName);
            }));
            if (eltToInheritFrom) {
              result.push(...findAttributeTargets(eltToInheritFrom, attrName));
            }
          }
          if (result.length === 0) {
            logError('The selector "' + attrTarget + '" on ' + attrName + " returned no matches!");
            return [DUMMY_ELT];
          } else {
            return result;
          }
        }
      }
    }
    function findThisElement(elt, attribute) {
      return asElement(getClosestMatch(elt, function(elt2) {
        return getAttributeValue(asElement(elt2), attribute) != null;
      }));
    }
    function getTarget(elt) {
      const targetStr = getClosestAttributeValue(elt, "hx-target");
      if (targetStr) {
        if (targetStr === "this") {
          return findThisElement(elt, "hx-target");
        } else {
          return querySelectorExt(elt, targetStr);
        }
      } else {
        const data = getInternalData(elt);
        if (data.boosted) {
          return getDocument().body;
        } else {
          return elt;
        }
      }
    }
    function shouldSettleAttribute(name) {
      return htmx.config.attributesToSettle.includes(name);
    }
    function cloneAttributes(mergeTo, mergeFrom) {
      forEach(Array.from(mergeTo.attributes), function(attr) {
        if (!mergeFrom.hasAttribute(attr.name) && shouldSettleAttribute(attr.name)) {
          mergeTo.removeAttribute(attr.name);
        }
      });
      forEach(mergeFrom.attributes, function(attr) {
        if (shouldSettleAttribute(attr.name)) {
          mergeTo.setAttribute(attr.name, attr.value);
        }
      });
    }
    function isInlineSwap(swapStyle, target) {
      const extensions2 = getExtensions(target);
      for (let i = 0; i < extensions2.length; i++) {
        const extension = extensions2[i];
        try {
          if (extension.isInlineSwap(swapStyle)) {
            return true;
          }
        } catch (e) {
          logError(e);
        }
      }
      return swapStyle === "outerHTML";
    }
    function oobSwap(oobValue, oobElement, settleInfo, rootNode) {
      rootNode = rootNode || getDocument();
      let selector = "#" + CSS.escape(getRawAttribute(oobElement, "id"));
      let swapStyle = "outerHTML";
      if (oobValue === "true") {
      } else if (oobValue.indexOf(":") > 0) {
        swapStyle = oobValue.substring(0, oobValue.indexOf(":"));
        selector = oobValue.substring(oobValue.indexOf(":") + 1);
      } else {
        swapStyle = oobValue;
      }
      oobElement.removeAttribute("hx-swap-oob");
      oobElement.removeAttribute("data-hx-swap-oob");
      const targets = querySelectorAllExt(rootNode, selector, false);
      if (targets.length) {
        forEach(
          targets,
          function(target) {
            let fragment;
            const oobElementClone = oobElement.cloneNode(true);
            fragment = getDocument().createDocumentFragment();
            fragment.appendChild(oobElementClone);
            if (!isInlineSwap(swapStyle, target)) {
              fragment = asParentNode(oobElementClone);
            }
            const beforeSwapDetails = { shouldSwap: true, target, fragment };
            if (!triggerEvent(target, "htmx:oobBeforeSwap", beforeSwapDetails)) return;
            target = beforeSwapDetails.target;
            if (beforeSwapDetails.shouldSwap) {
              handlePreservedElements(fragment);
              swapWithStyle(swapStyle, target, target, fragment, settleInfo);
              restorePreservedElements();
            }
            forEach(settleInfo.elts, function(elt) {
              triggerEvent(elt, "htmx:oobAfterSwap", beforeSwapDetails);
            });
          }
        );
        oobElement.parentNode.removeChild(oobElement);
      } else {
        oobElement.parentNode.removeChild(oobElement);
        triggerErrorEvent(getDocument().body, "htmx:oobErrorNoTarget", { content: oobElement, target: selector });
      }
      return oobValue;
    }
    function restorePreservedElements() {
      const pantry = find("#--htmx-preserve-pantry--");
      if (pantry) {
        for (const preservedElt of [...pantry.children]) {
          const existingElement = find("#" + preservedElt.id);
          existingElement.parentNode.moveBefore(preservedElt, existingElement);
          existingElement.remove();
        }
        pantry.remove();
      }
    }
    function handlePreservedElements(fragment) {
      forEach(findAll(fragment, "[hx-preserve], [data-hx-preserve]"), function(preservedElt) {
        const id = getAttributeValue(preservedElt, "id");
        const existingElement = getDocument().getElementById(id);
        if (existingElement != null) {
          if (preservedElt.moveBefore) {
            let pantry = find("#--htmx-preserve-pantry--");
            if (pantry == null) {
              getDocument().body.insertAdjacentHTML("afterend", "<div id='--htmx-preserve-pantry--'></div>");
              pantry = find("#--htmx-preserve-pantry--");
            }
            pantry.moveBefore(existingElement, null);
          } else {
            preservedElt.parentNode.replaceChild(existingElement, preservedElt);
          }
        }
      });
    }
    function handleAttributes(parentNode, fragment, settleInfo) {
      forEach(fragment.querySelectorAll("[id]"), function(newNode) {
        const id = getRawAttribute(newNode, "id");
        if (id && id.length > 0) {
          const parentElt2 = asParentNode(parentNode);
          const oldNode = parentElt2 && parentElt2.querySelector(CSS.escape(newNode.tagName) + "#" + CSS.escape(id));
          if (oldNode && oldNode !== parentElt2) {
            const newAttributes = newNode.cloneNode();
            cloneAttributes(newNode, oldNode);
            settleInfo.tasks.push(function() {
              cloneAttributes(newNode, newAttributes);
            });
          }
        }
      });
    }
    function makeAjaxLoadTask(child) {
      return function() {
        removeClassFromElement(child, htmx.config.addedClass);
        processNode(asElement(child));
        processFocus(asParentNode(child));
        triggerEvent(child, "htmx:load");
      };
    }
    function processFocus(child) {
      const autofocus = "[autofocus]";
      const autoFocusedElt = asHtmlElement(matches(child, autofocus) ? child : child.querySelector(autofocus));
      if (autoFocusedElt != null) {
        autoFocusedElt.focus();
      }
    }
    function insertNodesBefore(parentNode, insertBefore, fragment, settleInfo) {
      handleAttributes(parentNode, fragment, settleInfo);
      while (fragment.childNodes.length > 0) {
        const child = fragment.firstChild;
        addClassToElement(asElement(child), htmx.config.addedClass);
        parentNode.insertBefore(child, insertBefore);
        if (child.nodeType !== Node.TEXT_NODE && child.nodeType !== Node.COMMENT_NODE) {
          settleInfo.tasks.push(makeAjaxLoadTask(child));
        }
      }
    }
    function stringHash(string, hash) {
      let char = 0;
      while (char < string.length) {
        hash = (hash << 5) - hash + string.charCodeAt(char++) | 0;
      }
      return hash;
    }
    function attributeHash(elt) {
      let hash = 0;
      for (let i = 0; i < elt.attributes.length; i++) {
        const attribute = elt.attributes[i];
        if (attribute.value) {
          hash = stringHash(attribute.name, hash);
          hash = stringHash(attribute.value, hash);
        }
      }
      return hash;
    }
    function deInitOnHandlers(elt) {
      const internalData = getInternalData(elt);
      if (internalData.onHandlers) {
        for (let i = 0; i < internalData.onHandlers.length; i++) {
          const handlerInfo = internalData.onHandlers[i];
          removeEventListenerImpl(elt, handlerInfo.event, handlerInfo.listener);
        }
        delete internalData.onHandlers;
      }
    }
    function deInitNode(element) {
      const internalData = getInternalData(element);
      if (internalData.timeout) {
        clearTimeout(internalData.timeout);
      }
      if (internalData.listenerInfos) {
        forEach(internalData.listenerInfos, function(info) {
          if (info.on) {
            removeEventListenerImpl(info.on, info.trigger, info.listener);
          }
        });
      }
      deInitOnHandlers(element);
      forEach(Object.keys(internalData), function(key) {
        if (key !== "firstInitCompleted") delete internalData[key];
      });
    }
    function cleanUpElement(element) {
      triggerEvent(element, "htmx:beforeCleanupElement");
      deInitNode(element);
      forEach(element.children, function(child) {
        cleanUpElement(child);
      });
    }
    function swapOuterHTML(target, fragment, settleInfo) {
      if (target.tagName === "BODY") {
        return swapInnerHTML(target, fragment, settleInfo);
      }
      let newElt;
      const eltBeforeNewContent = target.previousSibling;
      const parentNode = parentElt(target);
      if (!parentNode) {
        return;
      }
      insertNodesBefore(parentNode, target, fragment, settleInfo);
      if (eltBeforeNewContent == null) {
        newElt = parentNode.firstChild;
      } else {
        newElt = eltBeforeNewContent.nextSibling;
      }
      settleInfo.elts = settleInfo.elts.filter(function(e) {
        return e !== target;
      });
      while (newElt && newElt !== target) {
        if (newElt instanceof Element) {
          settleInfo.elts.push(newElt);
        }
        newElt = newElt.nextSibling;
      }
      cleanUpElement(target);
      target.remove();
    }
    function swapAfterBegin(target, fragment, settleInfo) {
      return insertNodesBefore(target, target.firstChild, fragment, settleInfo);
    }
    function swapBeforeBegin(target, fragment, settleInfo) {
      return insertNodesBefore(parentElt(target), target, fragment, settleInfo);
    }
    function swapBeforeEnd(target, fragment, settleInfo) {
      return insertNodesBefore(target, null, fragment, settleInfo);
    }
    function swapAfterEnd(target, fragment, settleInfo) {
      return insertNodesBefore(parentElt(target), target.nextSibling, fragment, settleInfo);
    }
    function swapDelete(target) {
      cleanUpElement(target);
      const parent = parentElt(target);
      if (parent) {
        return parent.removeChild(target);
      }
    }
    function swapInnerHTML(target, fragment, settleInfo) {
      const firstChild = target.firstChild;
      insertNodesBefore(target, firstChild, fragment, settleInfo);
      if (firstChild) {
        while (firstChild.nextSibling) {
          cleanUpElement(firstChild.nextSibling);
          target.removeChild(firstChild.nextSibling);
        }
        cleanUpElement(firstChild);
        target.removeChild(firstChild);
      }
    }
    function swapWithStyle(swapStyle, elt, target, fragment, settleInfo) {
      switch (swapStyle) {
        case "none":
          return;
        case "outerHTML":
          swapOuterHTML(target, fragment, settleInfo);
          return;
        case "afterbegin":
          swapAfterBegin(target, fragment, settleInfo);
          return;
        case "beforebegin":
          swapBeforeBegin(target, fragment, settleInfo);
          return;
        case "beforeend":
          swapBeforeEnd(target, fragment, settleInfo);
          return;
        case "afterend":
          swapAfterEnd(target, fragment, settleInfo);
          return;
        case "delete":
          swapDelete(target);
          return;
        default:
          var extensions2 = getExtensions(elt);
          for (let i = 0; i < extensions2.length; i++) {
            const ext = extensions2[i];
            try {
              const newElements = ext.handleSwap(swapStyle, target, fragment, settleInfo);
              if (newElements) {
                if (Array.isArray(newElements)) {
                  for (let j = 0; j < newElements.length; j++) {
                    const child = newElements[j];
                    if (child.nodeType !== Node.TEXT_NODE && child.nodeType !== Node.COMMENT_NODE) {
                      settleInfo.tasks.push(makeAjaxLoadTask(child));
                    }
                  }
                }
                return;
              }
            } catch (e) {
              logError(e);
            }
          }
          if (swapStyle === "innerHTML") {
            swapInnerHTML(target, fragment, settleInfo);
          } else {
            swapWithStyle(htmx.config.defaultSwapStyle, elt, target, fragment, settleInfo);
          }
      }
    }
    function findAndSwapOobElements(fragment, settleInfo, rootNode) {
      var oobElts = findAll(fragment, "[hx-swap-oob], [data-hx-swap-oob]");
      forEach(oobElts, function(oobElement) {
        if (htmx.config.allowNestedOobSwaps || oobElement.parentElement === null) {
          const oobValue = getAttributeValue(oobElement, "hx-swap-oob");
          if (oobValue != null) {
            oobSwap(oobValue, oobElement, settleInfo, rootNode);
          }
        } else {
          oobElement.removeAttribute("hx-swap-oob");
          oobElement.removeAttribute("data-hx-swap-oob");
        }
      });
      return oobElts.length > 0;
    }
    function swap(target, content, swapSpec, swapOptions) {
      if (!swapOptions) {
        swapOptions = {};
      }
      let settleResolve = null;
      let settleReject = null;
      let doSwap = function() {
        maybeCall(swapOptions.beforeSwapCallback);
        target = resolveTarget(target);
        const rootNode = swapOptions.contextElement ? getRootNode(swapOptions.contextElement, false) : getDocument();
        const activeElt = document.activeElement;
        let selectionInfo = {};
        selectionInfo = {
          elt: activeElt,
          // @ts-ignore
          start: activeElt ? activeElt.selectionStart : null,
          // @ts-ignore
          end: activeElt ? activeElt.selectionEnd : null
        };
        const settleInfo = makeSettleInfo(target);
        if (swapSpec.swapStyle === "textContent") {
          target.textContent = content;
        } else {
          let fragment = makeFragment(content);
          settleInfo.title = swapOptions.title || fragment.title;
          if (swapOptions.historyRequest) {
            fragment = fragment.querySelector("[hx-history-elt],[data-hx-history-elt]") || fragment;
          }
          if (swapOptions.selectOOB) {
            const oobSelectValues = swapOptions.selectOOB.split(",");
            for (let i = 0; i < oobSelectValues.length; i++) {
              const oobSelectValue = oobSelectValues[i].split(":", 2);
              let id = oobSelectValue[0].trim();
              if (id.indexOf("#") === 0) {
                id = id.substring(1);
              }
              const oobValue = oobSelectValue[1] || "true";
              const oobElement = fragment.querySelector("#" + id);
              if (oobElement) {
                oobSwap(oobValue, oobElement, settleInfo, rootNode);
              }
            }
          }
          findAndSwapOobElements(fragment, settleInfo, rootNode);
          forEach(
            findAll(fragment, "template"),
            /** @param {HTMLTemplateElement} template */
            function(template) {
              if (template.content && findAndSwapOobElements(template.content, settleInfo, rootNode)) {
                template.remove();
              }
            }
          );
          if (swapOptions.select) {
            const newFragment = getDocument().createDocumentFragment();
            forEach(fragment.querySelectorAll(swapOptions.select), function(node) {
              newFragment.appendChild(node);
            });
            fragment = newFragment;
          }
          handlePreservedElements(fragment);
          swapWithStyle(swapSpec.swapStyle, swapOptions.contextElement, target, fragment, settleInfo);
          restorePreservedElements();
        }
        if (selectionInfo.elt && !bodyContains(selectionInfo.elt) && getRawAttribute(selectionInfo.elt, "id")) {
          const newActiveElt = document.getElementById(getRawAttribute(selectionInfo.elt, "id"));
          const focusOptions = { preventScroll: swapSpec.focusScroll !== void 0 ? !swapSpec.focusScroll : !htmx.config.defaultFocusScroll };
          if (newActiveElt) {
            if (selectionInfo.start && newActiveElt.setSelectionRange) {
              try {
                newActiveElt.setSelectionRange(selectionInfo.start, selectionInfo.end);
              } catch (e) {
              }
            }
            newActiveElt.focus(focusOptions);
          }
        }
        removeClassFromElement(target, htmx.config.swappingClass);
        forEach(settleInfo.elts, function(elt2) {
          if (elt2.classList) {
            addClassToElement(elt2, htmx.config.settlingClass);
          }
          triggerEvent(elt2, "htmx:afterSwap", swapOptions.eventInfo);
        });
        maybeCall(swapOptions.afterSwapCallback);
        if (!swapSpec.ignoreTitle) {
          handleTitle(settleInfo.title);
        }
        const doSettle = function() {
          forEach(settleInfo.tasks, function(task) {
            task.call();
          });
          forEach(settleInfo.elts, function(elt2) {
            if (elt2.classList) {
              removeClassFromElement(elt2, htmx.config.settlingClass);
            }
            triggerEvent(elt2, "htmx:afterSettle", swapOptions.eventInfo);
          });
          if (swapOptions.anchor) {
            const anchorTarget = asElement(resolveTarget("#" + swapOptions.anchor));
            if (anchorTarget) {
              anchorTarget.scrollIntoView({ block: "start", behavior: "auto" });
            }
          }
          updateScrollState(settleInfo.elts, swapSpec);
          maybeCall(swapOptions.afterSettleCallback);
          maybeCall(settleResolve);
        };
        if (swapSpec.settleDelay > 0) {
          getWindow().setTimeout(doSettle, swapSpec.settleDelay);
        } else {
          doSettle();
        }
      };
      let shouldTransition = htmx.config.globalViewTransitions;
      if (swapSpec.hasOwnProperty("transition")) {
        shouldTransition = swapSpec.transition;
      }
      const elt = swapOptions.contextElement || getDocument();
      if (shouldTransition && triggerEvent(elt, "htmx:beforeTransition", swapOptions.eventInfo) && typeof Promise !== "undefined" && // @ts-ignore experimental feature atm
      document.startViewTransition) {
        const settlePromise = new Promise(function(_resolve, _reject) {
          settleResolve = _resolve;
          settleReject = _reject;
        });
        const innerDoSwap = doSwap;
        doSwap = function() {
          document.startViewTransition(function() {
            innerDoSwap();
            return settlePromise;
          });
        };
      }
      try {
        if (swapSpec?.swapDelay && swapSpec.swapDelay > 0) {
          getWindow().setTimeout(doSwap, swapSpec.swapDelay);
        } else {
          doSwap();
        }
      } catch (e) {
        triggerErrorEvent(elt, "htmx:swapError", swapOptions.eventInfo);
        maybeCall(settleReject);
        throw e;
      }
    }
    function handleTriggerHeader(xhr, header, elt) {
      const triggerBody = xhr.getResponseHeader(header);
      if (triggerBody.indexOf("{") === 0) {
        const triggers = parseJSON(triggerBody);
        for (const eventName in triggers) {
          if (triggers.hasOwnProperty(eventName)) {
            let detail = triggers[eventName];
            if (isRawObject(detail)) {
              elt = detail.target !== void 0 ? detail.target : elt;
            } else {
              detail = { value: detail };
            }
            triggerEvent(elt, eventName, detail);
          }
        }
      } else {
        const eventNames = triggerBody.split(",");
        for (let i = 0; i < eventNames.length; i++) {
          triggerEvent(elt, eventNames[i].trim(), []);
        }
      }
    }
    const WHITESPACE = /\s/;
    const WHITESPACE_OR_COMMA = /[\s,]/;
    const SYMBOL_START = /[_$a-zA-Z]/;
    const SYMBOL_CONT = /[_$a-zA-Z0-9]/;
    const STRINGISH_START = ['"', "'", "/"];
    const NOT_WHITESPACE = /[^\s]/;
    const COMBINED_SELECTOR_START = /[{(]/;
    const COMBINED_SELECTOR_END = /[})]/;
    function tokenizeString(str2) {
      const tokens = [];
      let position = 0;
      while (position < str2.length) {
        if (SYMBOL_START.exec(str2.charAt(position))) {
          var startPosition = position;
          while (SYMBOL_CONT.exec(str2.charAt(position + 1))) {
            position++;
          }
          tokens.push(str2.substring(startPosition, position + 1));
        } else if (STRINGISH_START.indexOf(str2.charAt(position)) !== -1) {
          const startChar = str2.charAt(position);
          var startPosition = position;
          position++;
          while (position < str2.length && str2.charAt(position) !== startChar) {
            if (str2.charAt(position) === "\\") {
              position++;
            }
            position++;
          }
          tokens.push(str2.substring(startPosition, position + 1));
        } else {
          const symbol = str2.charAt(position);
          tokens.push(symbol);
        }
        position++;
      }
      return tokens;
    }
    function isPossibleRelativeReference(token, last, paramName) {
      return SYMBOL_START.exec(token.charAt(0)) && token !== "true" && token !== "false" && token !== "this" && token !== paramName && last !== ".";
    }
    function maybeGenerateConditional(elt, tokens, paramName) {
      if (tokens[0] === "[") {
        tokens.shift();
        let bracketCount = 1;
        let conditionalSource = " return (function(" + paramName + "){ return (";
        let last = null;
        while (tokens.length > 0) {
          const token = tokens[0];
          if (token === "]") {
            bracketCount--;
            if (bracketCount === 0) {
              if (last === null) {
                conditionalSource = conditionalSource + "true";
              }
              tokens.shift();
              conditionalSource += ")})";
              try {
                const conditionFunction = maybeEval(
                  elt,
                  function() {
                    return Function(conditionalSource)();
                  },
                  function() {
                    return true;
                  }
                );
                conditionFunction.source = conditionalSource;
                return conditionFunction;
              } catch (e) {
                triggerErrorEvent(getDocument().body, "htmx:syntax:error", { error: e, source: conditionalSource });
                return null;
              }
            }
          } else if (token === "[") {
            bracketCount++;
          }
          if (isPossibleRelativeReference(token, last, paramName)) {
            conditionalSource += "((" + paramName + "." + token + ") ? (" + paramName + "." + token + ") : (window." + token + "))";
          } else {
            conditionalSource = conditionalSource + token;
          }
          last = tokens.shift();
        }
      }
    }
    function consumeUntil(tokens, match) {
      let result = "";
      while (tokens.length > 0 && !match.test(tokens[0])) {
        result += tokens.shift();
      }
      return result;
    }
    function consumeCSSSelector(tokens) {
      let result;
      if (tokens.length > 0 && COMBINED_SELECTOR_START.test(tokens[0])) {
        tokens.shift();
        result = consumeUntil(tokens, COMBINED_SELECTOR_END).trim();
        tokens.shift();
      } else {
        result = consumeUntil(tokens, WHITESPACE_OR_COMMA);
      }
      return result;
    }
    const INPUT_SELECTOR = "input, textarea, select";
    function parseAndCacheTrigger(elt, explicitTrigger, cache) {
      const triggerSpecs = [];
      const tokens = tokenizeString(explicitTrigger);
      do {
        consumeUntil(tokens, NOT_WHITESPACE);
        const initialLength = tokens.length;
        const trigger = consumeUntil(tokens, /[,\[\s]/);
        if (trigger !== "") {
          if (trigger === "every") {
            const every = { trigger: "every" };
            consumeUntil(tokens, NOT_WHITESPACE);
            every.pollInterval = parseInterval(consumeUntil(tokens, /[,\[\s]/));
            consumeUntil(tokens, NOT_WHITESPACE);
            var eventFilter = maybeGenerateConditional(elt, tokens, "event");
            if (eventFilter) {
              every.eventFilter = eventFilter;
            }
            triggerSpecs.push(every);
          } else {
            const triggerSpec = { trigger };
            var eventFilter = maybeGenerateConditional(elt, tokens, "event");
            if (eventFilter) {
              triggerSpec.eventFilter = eventFilter;
            }
            consumeUntil(tokens, NOT_WHITESPACE);
            while (tokens.length > 0 && tokens[0] !== ",") {
              const token = tokens.shift();
              if (token === "changed") {
                triggerSpec.changed = true;
              } else if (token === "once") {
                triggerSpec.once = true;
              } else if (token === "consume") {
                triggerSpec.consume = true;
              } else if (token === "delay" && tokens[0] === ":") {
                tokens.shift();
                triggerSpec.delay = parseInterval(consumeUntil(tokens, WHITESPACE_OR_COMMA));
              } else if (token === "from" && tokens[0] === ":") {
                tokens.shift();
                if (COMBINED_SELECTOR_START.test(tokens[0])) {
                  var from_arg = consumeCSSSelector(tokens);
                } else {
                  var from_arg = consumeUntil(tokens, WHITESPACE_OR_COMMA);
                  if (from_arg === "closest" || from_arg === "find" || from_arg === "next" || from_arg === "previous") {
                    tokens.shift();
                    const selector = consumeCSSSelector(tokens);
                    if (selector.length > 0) {
                      from_arg += " " + selector;
                    }
                  }
                }
                triggerSpec.from = from_arg;
              } else if (token === "target" && tokens[0] === ":") {
                tokens.shift();
                triggerSpec.target = consumeCSSSelector(tokens);
              } else if (token === "throttle" && tokens[0] === ":") {
                tokens.shift();
                triggerSpec.throttle = parseInterval(consumeUntil(tokens, WHITESPACE_OR_COMMA));
              } else if (token === "queue" && tokens[0] === ":") {
                tokens.shift();
                triggerSpec.queue = consumeUntil(tokens, WHITESPACE_OR_COMMA);
              } else if (token === "root" && tokens[0] === ":") {
                tokens.shift();
                triggerSpec[token] = consumeCSSSelector(tokens);
              } else if (token === "threshold" && tokens[0] === ":") {
                tokens.shift();
                triggerSpec[token] = consumeUntil(tokens, WHITESPACE_OR_COMMA);
              } else {
                triggerErrorEvent(elt, "htmx:syntax:error", { token: tokens.shift() });
              }
              consumeUntil(tokens, NOT_WHITESPACE);
            }
            triggerSpecs.push(triggerSpec);
          }
        }
        if (tokens.length === initialLength) {
          triggerErrorEvent(elt, "htmx:syntax:error", { token: tokens.shift() });
        }
        consumeUntil(tokens, NOT_WHITESPACE);
      } while (tokens[0] === "," && tokens.shift());
      if (cache) {
        cache[explicitTrigger] = triggerSpecs;
      }
      return triggerSpecs;
    }
    function getTriggerSpecs(elt) {
      const explicitTrigger = getAttributeValue(elt, "hx-trigger");
      let triggerSpecs = [];
      if (explicitTrigger) {
        const cache = htmx.config.triggerSpecsCache;
        triggerSpecs = cache && cache[explicitTrigger] || parseAndCacheTrigger(elt, explicitTrigger, cache);
      }
      if (triggerSpecs.length > 0) {
        return triggerSpecs;
      } else if (matches(elt, "form")) {
        return [{ trigger: "submit" }];
      } else if (matches(elt, 'input[type="button"], input[type="submit"]')) {
        return [{ trigger: "click" }];
      } else if (matches(elt, INPUT_SELECTOR)) {
        return [{ trigger: "change" }];
      } else {
        return [{ trigger: "click" }];
      }
    }
    function cancelPolling(elt) {
      getInternalData(elt).cancelled = true;
    }
    function processPolling(elt, handler, spec) {
      const nodeData = getInternalData(elt);
      nodeData.timeout = getWindow().setTimeout(function() {
        if (bodyContains(elt) && nodeData.cancelled !== true) {
          if (!maybeFilterEvent(spec, elt, makeEvent("hx:poll:trigger", {
            triggerSpec: spec,
            target: elt
          }))) {
            handler(elt);
          }
          processPolling(elt, handler, spec);
        }
      }, spec.pollInterval);
    }
    function isLocalLink(elt) {
      return location.hostname === elt.hostname && getRawAttribute(elt, "href") && getRawAttribute(elt, "href").indexOf("#") !== 0;
    }
    function eltIsDisabled(elt) {
      return closest(elt, htmx.config.disableSelector);
    }
    function boostElement(elt, nodeData, triggerSpecs) {
      if (elt instanceof HTMLAnchorElement && isLocalLink(elt) && (elt.target === "" || elt.target === "_self") || elt.tagName === "FORM" && String(getRawAttribute(elt, "method")).toLowerCase() !== "dialog") {
        nodeData.boosted = true;
        let verb, path;
        if (elt.tagName === "A") {
          verb = /** @type HttpVerb */
          "get";
          path = getRawAttribute(elt, "href");
        } else {
          const rawAttribute = getRawAttribute(elt, "method");
          verb = /** @type HttpVerb */
          rawAttribute ? rawAttribute.toLowerCase() : "get";
          path = getRawAttribute(elt, "action");
          if (path == null || path === "") {
            path = location.href;
          }
          if (verb === "get" && path.includes("?")) {
            path = path.replace(/\?[^#]+/, "");
          }
        }
        triggerSpecs.forEach(function(triggerSpec) {
          addEventListener(elt, function(node, evt) {
            const elt2 = asElement(node);
            if (eltIsDisabled(elt2)) {
              cleanUpElement(elt2);
              return;
            }
            issueAjaxRequest(verb, path, elt2, evt);
          }, nodeData, triggerSpec, true);
        });
      }
    }
    function shouldCancel(evt, elt) {
      if (evt.type === "submit" && elt.tagName === "FORM") {
        return true;
      } else if (evt.type === "click") {
        const btn = (
          /** @type {HTMLButtonElement|HTMLInputElement|null} */
          elt.closest('input[type="submit"], button')
        );
        if (btn && btn.form && btn.type === "submit") {
          return true;
        }
        const link = elt.closest("a");
        const samePageAnchor = /^#.+/;
        if (link && link.href && !samePageAnchor.test(link.getAttribute("href"))) {
          return true;
        }
      }
      return false;
    }
    function ignoreBoostedAnchorCtrlClick(elt, evt) {
      return getInternalData(elt).boosted && elt instanceof HTMLAnchorElement && evt.type === "click" && // @ts-ignore this will resolve to undefined for events that don't define those properties, which is fine
      (evt.ctrlKey || evt.metaKey);
    }
    function maybeFilterEvent(triggerSpec, elt, evt) {
      const eventFilter = triggerSpec.eventFilter;
      if (eventFilter) {
        try {
          return eventFilter.call(elt, evt) !== true;
        } catch (e) {
          const source = eventFilter.source;
          triggerErrorEvent(getDocument().body, "htmx:eventFilter:error", { error: e, source });
          return true;
        }
      }
      return false;
    }
    function addEventListener(elt, handler, nodeData, triggerSpec, explicitCancel) {
      const elementData = getInternalData(elt);
      let eltsToListenOn;
      if (triggerSpec.from) {
        eltsToListenOn = querySelectorAllExt(elt, triggerSpec.from);
      } else {
        eltsToListenOn = [elt];
      }
      if (triggerSpec.changed) {
        if (!("lastValue" in elementData)) {
          elementData.lastValue = /* @__PURE__ */ new WeakMap();
        }
        eltsToListenOn.forEach(function(eltToListenOn) {
          if (!elementData.lastValue.has(triggerSpec)) {
            elementData.lastValue.set(triggerSpec, /* @__PURE__ */ new WeakMap());
          }
          elementData.lastValue.get(triggerSpec).set(eltToListenOn, eltToListenOn.value);
        });
      }
      forEach(eltsToListenOn, function(eltToListenOn) {
        const eventListener = function(evt) {
          if (!bodyContains(elt)) {
            eltToListenOn.removeEventListener(triggerSpec.trigger, eventListener);
            return;
          }
          if (ignoreBoostedAnchorCtrlClick(elt, evt)) {
            return;
          }
          if (explicitCancel || shouldCancel(evt, eltToListenOn)) {
            evt.preventDefault();
          }
          if (maybeFilterEvent(triggerSpec, elt, evt)) {
            return;
          }
          const eventData = getInternalData(evt);
          eventData.triggerSpec = triggerSpec;
          if (eventData.handledFor == null) {
            eventData.handledFor = [];
          }
          if (eventData.handledFor.indexOf(elt) < 0) {
            eventData.handledFor.push(elt);
            if (triggerSpec.consume) {
              evt.stopPropagation();
            }
            if (triggerSpec.target && evt.target) {
              if (!matches(asElement(evt.target), triggerSpec.target)) {
                return;
              }
            }
            if (triggerSpec.once) {
              if (elementData.triggeredOnce) {
                return;
              } else {
                elementData.triggeredOnce = true;
              }
            }
            if (triggerSpec.changed) {
              const node = evt.target;
              const value = node.value;
              const lastValue = elementData.lastValue.get(triggerSpec);
              if (lastValue.has(node) && lastValue.get(node) === value) {
                return;
              }
              lastValue.set(node, value);
            }
            if (elementData.delayed) {
              clearTimeout(elementData.delayed);
            }
            if (elementData.throttle) {
              return;
            }
            if (triggerSpec.throttle > 0) {
              if (!elementData.throttle) {
                triggerEvent(elt, "htmx:trigger");
                handler(elt, evt);
                elementData.throttle = getWindow().setTimeout(function() {
                  elementData.throttle = null;
                }, triggerSpec.throttle);
              }
            } else if (triggerSpec.delay > 0) {
              elementData.delayed = getWindow().setTimeout(function() {
                triggerEvent(elt, "htmx:trigger");
                handler(elt, evt);
              }, triggerSpec.delay);
            } else {
              triggerEvent(elt, "htmx:trigger");
              handler(elt, evt);
            }
          }
        };
        if (nodeData.listenerInfos == null) {
          nodeData.listenerInfos = [];
        }
        nodeData.listenerInfos.push({
          trigger: triggerSpec.trigger,
          listener: eventListener,
          on: eltToListenOn
        });
        eltToListenOn.addEventListener(triggerSpec.trigger, eventListener);
      });
    }
    let windowIsScrolling = false;
    let scrollHandler = null;
    function initScrollHandler() {
      if (!scrollHandler) {
        scrollHandler = function() {
          windowIsScrolling = true;
        };
        window.addEventListener("scroll", scrollHandler);
        window.addEventListener("resize", scrollHandler);
        setInterval(function() {
          if (windowIsScrolling) {
            windowIsScrolling = false;
            forEach(getDocument().querySelectorAll("[hx-trigger*='revealed'],[data-hx-trigger*='revealed']"), function(elt) {
              maybeReveal(elt);
            });
          }
        }, 200);
      }
    }
    function maybeReveal(elt) {
      if (!hasAttribute(elt, "data-hx-revealed") && isScrolledIntoView(elt)) {
        elt.setAttribute("data-hx-revealed", "true");
        const nodeData = getInternalData(elt);
        if (nodeData.initHash) {
          triggerEvent(elt, "revealed");
        } else {
          elt.addEventListener("htmx:afterProcessNode", function() {
            triggerEvent(elt, "revealed");
          }, { once: true });
        }
      }
    }
    function loadImmediately(elt, handler, nodeData, delay) {
      const load = function() {
        if (!nodeData.loaded) {
          nodeData.loaded = true;
          triggerEvent(elt, "htmx:trigger");
          handler(elt);
        }
      };
      if (delay > 0) {
        getWindow().setTimeout(load, delay);
      } else {
        load();
      }
    }
    function processVerbs(elt, nodeData, triggerSpecs) {
      let explicitAction = false;
      forEach(VERBS, function(verb) {
        if (hasAttribute(elt, "hx-" + verb)) {
          const path = getAttributeValue(elt, "hx-" + verb);
          explicitAction = true;
          nodeData.path = path;
          nodeData.verb = verb;
          triggerSpecs.forEach(function(triggerSpec) {
            addTriggerHandler(elt, triggerSpec, nodeData, function(node, evt) {
              const elt2 = asElement(node);
              if (eltIsDisabled(elt2)) {
                cleanUpElement(elt2);
                return;
              }
              issueAjaxRequest(verb, path, elt2, evt);
            });
          });
        }
      });
      return explicitAction;
    }
    function addTriggerHandler(elt, triggerSpec, nodeData, handler) {
      if (triggerSpec.trigger === "revealed") {
        initScrollHandler();
        addEventListener(elt, handler, nodeData, triggerSpec);
        maybeReveal(asElement(elt));
      } else if (triggerSpec.trigger === "intersect") {
        const observerOptions = {};
        if (triggerSpec.root) {
          observerOptions.root = querySelectorExt(elt, triggerSpec.root);
        }
        if (triggerSpec.threshold) {
          observerOptions.threshold = parseFloat(triggerSpec.threshold);
        }
        const observer = new IntersectionObserver(function(entries) {
          for (let i = 0; i < entries.length; i++) {
            const entry = entries[i];
            if (entry.isIntersecting) {
              triggerEvent(elt, "intersect");
              break;
            }
          }
        }, observerOptions);
        observer.observe(asElement(elt));
        addEventListener(asElement(elt), handler, nodeData, triggerSpec);
      } else if (!nodeData.firstInitCompleted && triggerSpec.trigger === "load") {
        if (!maybeFilterEvent(triggerSpec, elt, makeEvent("load", { elt }))) {
          loadImmediately(asElement(elt), handler, nodeData, triggerSpec.delay);
        }
      } else if (triggerSpec.pollInterval > 0) {
        nodeData.polling = true;
        processPolling(asElement(elt), handler, triggerSpec);
      } else {
        addEventListener(elt, handler, nodeData, triggerSpec);
      }
    }
    function shouldProcessHxOn(node) {
      const elt = asElement(node);
      if (!elt) {
        return false;
      }
      const attributes = elt.attributes;
      for (let j = 0; j < attributes.length; j++) {
        const attrName = attributes[j].name;
        if (startsWith(attrName, "hx-on:") || startsWith(attrName, "data-hx-on:") || startsWith(attrName, "hx-on-") || startsWith(attrName, "data-hx-on-")) {
          return true;
        }
      }
      return false;
    }
    const HX_ON_QUERY = new XPathEvaluator().createExpression('.//*[@*[ starts-with(name(), "hx-on:") or starts-with(name(), "data-hx-on:") or starts-with(name(), "hx-on-") or starts-with(name(), "data-hx-on-") ]]');
    function processHXOnRoot(elt, elements) {
      if (shouldProcessHxOn(elt)) {
        elements.push(asElement(elt));
      }
      const iter = HX_ON_QUERY.evaluate(elt);
      let node = null;
      while (node = iter.iterateNext()) elements.push(asElement(node));
    }
    function findHxOnWildcardElements(elt) {
      const elements = [];
      if (elt instanceof DocumentFragment) {
        for (const child of elt.childNodes) {
          processHXOnRoot(child, elements);
        }
      } else {
        processHXOnRoot(elt, elements);
      }
      return elements;
    }
    function findElementsToProcess(elt) {
      if (elt.querySelectorAll) {
        const boostedSelector = ", [hx-boost] a, [data-hx-boost] a, a[hx-boost], a[data-hx-boost]";
        const extensionSelectors = [];
        for (const e in extensions) {
          const extension = extensions[e];
          if (extension.getSelectors) {
            var selectors = extension.getSelectors();
            if (selectors) {
              extensionSelectors.push(selectors);
            }
          }
        }
        const results = elt.querySelectorAll(VERB_SELECTOR + boostedSelector + ", form, [type='submit'], [hx-ext], [data-hx-ext], [hx-trigger], [data-hx-trigger]" + extensionSelectors.flat().map((s) => ", " + s).join(""));
        return results;
      } else {
        return [];
      }
    }
    function maybeSetLastButtonClicked(evt) {
      const elt = getTargetButton(evt.target);
      const internalData = getRelatedFormData(evt);
      if (internalData) {
        internalData.lastButtonClicked = elt;
      }
    }
    function maybeUnsetLastButtonClicked(evt) {
      const internalData = getRelatedFormData(evt);
      if (internalData) {
        internalData.lastButtonClicked = null;
      }
    }
    function getTargetButton(target) {
      return (
        /** @type {HTMLButtonElement|HTMLInputElement|null} */
        closest(asElement(target), "button, input[type='submit']")
      );
    }
    function getRelatedForm(elt) {
      return elt.form || closest(elt, "form");
    }
    function getRelatedFormData(evt) {
      const elt = getTargetButton(evt.target);
      if (!elt) {
        return;
      }
      const form = getRelatedForm(elt);
      if (!form) {
        return;
      }
      return getInternalData(form);
    }
    function initButtonTracking(elt) {
      elt.addEventListener("click", maybeSetLastButtonClicked);
      elt.addEventListener("focusin", maybeSetLastButtonClicked);
      elt.addEventListener("focusout", maybeUnsetLastButtonClicked);
    }
    function addHxOnEventHandler(elt, eventName, code) {
      const nodeData = getInternalData(elt);
      if (!Array.isArray(nodeData.onHandlers)) {
        nodeData.onHandlers = [];
      }
      let func;
      const listener = function(e) {
        maybeEval(elt, function() {
          if (eltIsDisabled(elt)) {
            return;
          }
          if (!func) {
            func = new Function("event", code);
          }
          func.call(elt, e);
        });
      };
      elt.addEventListener(eventName, listener);
      nodeData.onHandlers.push({ event: eventName, listener });
    }
    function processHxOnWildcard(elt) {
      deInitOnHandlers(elt);
      for (let i = 0; i < elt.attributes.length; i++) {
        const name = elt.attributes[i].name;
        const value = elt.attributes[i].value;
        if (startsWith(name, "hx-on") || startsWith(name, "data-hx-on")) {
          const afterOnPosition = name.indexOf("-on") + 3;
          const nextChar = name.slice(afterOnPosition, afterOnPosition + 1);
          if (nextChar === "-" || nextChar === ":") {
            let eventName = name.slice(afterOnPosition + 1);
            if (startsWith(eventName, ":")) {
              eventName = "htmx" + eventName;
            } else if (startsWith(eventName, "-")) {
              eventName = "htmx:" + eventName.slice(1);
            } else if (startsWith(eventName, "htmx-")) {
              eventName = "htmx:" + eventName.slice(5);
            }
            addHxOnEventHandler(elt, eventName, value);
          }
        }
      }
    }
    function initNode(elt) {
      triggerEvent(elt, "htmx:beforeProcessNode");
      const nodeData = getInternalData(elt);
      const triggerSpecs = getTriggerSpecs(elt);
      const hasExplicitHttpAction = processVerbs(elt, nodeData, triggerSpecs);
      if (!hasExplicitHttpAction) {
        if (getClosestAttributeValue(elt, "hx-boost") === "true") {
          boostElement(elt, nodeData, triggerSpecs);
        } else if (hasAttribute(elt, "hx-trigger")) {
          triggerSpecs.forEach(function(triggerSpec) {
            addTriggerHandler(elt, triggerSpec, nodeData, function() {
            });
          });
        }
      }
      if (elt.tagName === "FORM" || getRawAttribute(elt, "type") === "submit" && hasAttribute(elt, "form")) {
        initButtonTracking(elt);
      }
      nodeData.firstInitCompleted = true;
      triggerEvent(elt, "htmx:afterProcessNode");
    }
    function maybeDeInitAndHash(elt) {
      if (!(elt instanceof Element)) {
        return false;
      }
      const nodeData = getInternalData(elt);
      const hash = attributeHash(elt);
      if (nodeData.initHash !== hash) {
        deInitNode(elt);
        nodeData.initHash = hash;
        return true;
      }
      return false;
    }
    function processNode(elt) {
      elt = resolveTarget(elt);
      if (eltIsDisabled(elt)) {
        cleanUpElement(elt);
        return;
      }
      const elementsToInit = [];
      if (maybeDeInitAndHash(elt)) {
        elementsToInit.push(elt);
      }
      forEach(findElementsToProcess(elt), function(child) {
        if (eltIsDisabled(child)) {
          cleanUpElement(child);
          return;
        }
        if (maybeDeInitAndHash(child)) {
          elementsToInit.push(child);
        }
      });
      forEach(findHxOnWildcardElements(elt), processHxOnWildcard);
      forEach(elementsToInit, initNode);
    }
    function kebabEventName(str2) {
      return str2.replace(/([a-z0-9])([A-Z])/g, "$1-$2").toLowerCase();
    }
    function makeEvent(eventName, detail) {
      return new CustomEvent(eventName, { bubbles: true, cancelable: true, composed: true, detail });
    }
    function triggerErrorEvent(elt, eventName, detail) {
      triggerEvent(elt, eventName, mergeObjects({ error: eventName }, detail));
    }
    function ignoreEventForLogging(eventName) {
      return eventName === "htmx:afterProcessNode";
    }
    function withExtensions(elt, toDo, extensionsToIgnore) {
      forEach(getExtensions(elt, [], extensionsToIgnore), function(extension) {
        try {
          toDo(extension);
        } catch (e) {
          logError(e);
        }
      });
    }
    function logError(msg) {
      console.error(msg);
    }
    function triggerEvent(elt, eventName, detail) {
      elt = resolveTarget(elt);
      if (detail == null) {
        detail = {};
      }
      detail.elt = elt;
      const event = makeEvent(eventName, detail);
      if (htmx.logger && !ignoreEventForLogging(eventName)) {
        htmx.logger(elt, eventName, detail);
      }
      if (detail.error) {
        logError(detail.error + (detail.target ? ", " + detail.target : ""));
        triggerEvent(elt, "htmx:error", { errorInfo: detail });
      }
      let eventResult = elt.dispatchEvent(event);
      const kebabName = kebabEventName(eventName);
      if (eventResult && kebabName !== eventName) {
        const kebabedEvent = makeEvent(kebabName, event.detail);
        eventResult = eventResult && elt.dispatchEvent(kebabedEvent);
      }
      withExtensions(asElement(elt), function(extension) {
        eventResult = eventResult && (extension.onEvent(eventName, event) !== false && !event.defaultPrevented);
      });
      return eventResult;
    }
    let currentPathForHistory;
    function setCurrentPathForHistory(path) {
      currentPathForHistory = path;
      if (canAccessLocalStorage()) {
        sessionStorage.setItem("htmx-current-path-for-history", path);
      }
    }
    setCurrentPathForHistory(location.pathname + location.search);
    function getHistoryElement() {
      const historyElt = getDocument().querySelector("[hx-history-elt],[data-hx-history-elt]");
      return historyElt || getDocument().body;
    }
    function saveToHistoryCache(url, rootElt) {
      if (!canAccessLocalStorage()) {
        return;
      }
      const innerHTML = cleanInnerHtmlForHistory(rootElt);
      const title = getDocument().title;
      const scroll = window.scrollY;
      if (htmx.config.historyCacheSize <= 0) {
        sessionStorage.removeItem("htmx-history-cache");
        return;
      }
      url = normalizePath(url);
      const historyCache = parseJSON(sessionStorage.getItem("htmx-history-cache")) || [];
      for (let i = 0; i < historyCache.length; i++) {
        if (historyCache[i].url === url) {
          historyCache.splice(i, 1);
          break;
        }
      }
      const newHistoryItem = { url, content: innerHTML, title, scroll };
      triggerEvent(getDocument().body, "htmx:historyItemCreated", { item: newHistoryItem, cache: historyCache });
      historyCache.push(newHistoryItem);
      while (historyCache.length > htmx.config.historyCacheSize) {
        historyCache.shift();
      }
      while (historyCache.length > 0) {
        try {
          sessionStorage.setItem("htmx-history-cache", JSON.stringify(historyCache));
          break;
        } catch (e) {
          triggerErrorEvent(getDocument().body, "htmx:historyCacheError", { cause: e, cache: historyCache });
          historyCache.shift();
        }
      }
    }
    function getCachedHistory(url) {
      if (!canAccessLocalStorage()) {
        return null;
      }
      url = normalizePath(url);
      const historyCache = parseJSON(sessionStorage.getItem("htmx-history-cache")) || [];
      for (let i = 0; i < historyCache.length; i++) {
        if (historyCache[i].url === url) {
          return historyCache[i];
        }
      }
      return null;
    }
    function cleanInnerHtmlForHistory(elt) {
      const className = htmx.config.requestClass;
      const clone = (
        /** @type Element */
        elt.cloneNode(true)
      );
      forEach(findAll(clone, "." + className), function(child) {
        removeClassFromElement(child, className);
      });
      forEach(findAll(clone, "[data-disabled-by-htmx]"), function(child) {
        child.removeAttribute("disabled");
      });
      return clone.innerHTML;
    }
    function saveCurrentPageToHistory() {
      const elt = getHistoryElement();
      let path = currentPathForHistory;
      if (canAccessLocalStorage()) {
        path = sessionStorage.getItem("htmx-current-path-for-history");
      }
      path = path || location.pathname + location.search;
      const disableHistoryCache = getDocument().querySelector('[hx-history="false" i],[data-hx-history="false" i]');
      if (!disableHistoryCache) {
        triggerEvent(getDocument().body, "htmx:beforeHistorySave", { path, historyElt: elt });
        saveToHistoryCache(path, elt);
      }
      if (htmx.config.historyEnabled) history.replaceState({ htmx: true }, getDocument().title, location.href);
    }
    function pushUrlIntoHistory(path) {
      if (htmx.config.getCacheBusterParam) {
        path = path.replace(/org\.htmx\.cache-buster=[^&]*&?/, "");
        if (endsWith(path, "&") || endsWith(path, "?")) {
          path = path.slice(0, -1);
        }
      }
      if (htmx.config.historyEnabled) {
        history.pushState({ htmx: true }, "", path);
      }
      setCurrentPathForHistory(path);
    }
    function replaceUrlInHistory(path) {
      if (htmx.config.historyEnabled) history.replaceState({ htmx: true }, "", path);
      setCurrentPathForHistory(path);
    }
    function settleImmediately(tasks) {
      forEach(tasks, function(task) {
        task.call(void 0);
      });
    }
    function loadHistoryFromServer(path) {
      const request = new XMLHttpRequest();
      const swapSpec = { swapStyle: "innerHTML", swapDelay: 0, settleDelay: 0 };
      const details = { path, xhr: request, historyElt: getHistoryElement(), swapSpec };
      request.open("GET", path, true);
      if (htmx.config.historyRestoreAsHxRequest) {
        request.setRequestHeader("HX-Request", "true");
      }
      request.setRequestHeader("HX-History-Restore-Request", "true");
      request.setRequestHeader("HX-Current-URL", location.href);
      request.onload = function() {
        if (this.status >= 200 && this.status < 400) {
          details.response = this.response;
          triggerEvent(getDocument().body, "htmx:historyCacheMissLoad", details);
          swap(details.historyElt, details.response, swapSpec, {
            contextElement: details.historyElt,
            historyRequest: true
          });
          setCurrentPathForHistory(details.path);
          triggerEvent(getDocument().body, "htmx:historyRestore", { path, cacheMiss: true, serverResponse: details.response });
        } else {
          triggerErrorEvent(getDocument().body, "htmx:historyCacheMissLoadError", details);
        }
      };
      if (triggerEvent(getDocument().body, "htmx:historyCacheMiss", details)) {
        request.send();
      }
    }
    function restoreHistory(path) {
      saveCurrentPageToHistory();
      path = path || location.pathname + location.search;
      const cached = getCachedHistory(path);
      if (cached) {
        const swapSpec = { swapStyle: "innerHTML", swapDelay: 0, settleDelay: 0, scroll: cached.scroll };
        const details = { path, item: cached, historyElt: getHistoryElement(), swapSpec };
        if (triggerEvent(getDocument().body, "htmx:historyCacheHit", details)) {
          swap(details.historyElt, cached.content, swapSpec, {
            contextElement: details.historyElt,
            title: cached.title
          });
          setCurrentPathForHistory(details.path);
          triggerEvent(getDocument().body, "htmx:historyRestore", details);
        }
      } else {
        if (htmx.config.refreshOnHistoryMiss) {
          htmx.location.reload(true);
        } else {
          loadHistoryFromServer(path);
        }
      }
    }
    function addRequestIndicatorClasses(elt) {
      let indicators = (
        /** @type Element[] */
        findAttributeTargets(elt, "hx-indicator")
      );
      if (indicators == null) {
        indicators = [elt];
      }
      forEach(indicators, function(ic) {
        const internalData = getInternalData(ic);
        internalData.requestCount = (internalData.requestCount || 0) + 1;
        addClassToElement(ic, htmx.config.requestClass);
      });
      return indicators;
    }
    function disableElements(elt) {
      let disabledElts = (
        /** @type Element[] */
        findAttributeTargets(elt, "hx-disabled-elt")
      );
      if (disabledElts == null) {
        disabledElts = [];
      }
      forEach(disabledElts, function(disabledElement) {
        const internalData = getInternalData(disabledElement);
        internalData.requestCount = (internalData.requestCount || 0) + 1;
        if (!disabledElement.hasAttribute("disabled")) {
          disabledElement.setAttribute("disabled", "");
          disabledElement.setAttribute("data-disabled-by-htmx", "");
        }
      });
      return disabledElts;
    }
    function removeRequestIndicators(indicators, disabled) {
      forEach(indicators.concat(disabled), function(ele) {
        const internalData = getInternalData(ele);
        internalData.requestCount = (internalData.requestCount || 1) - 1;
      });
      forEach(indicators, function(ic) {
        const internalData = getInternalData(ic);
        if (internalData.requestCount === 0) {
          removeClassFromElement(ic, htmx.config.requestClass);
        }
      });
      forEach(disabled, function(disabledElement) {
        const internalData = getInternalData(disabledElement);
        if (internalData.requestCount === 0 && disabledElement.hasAttribute("data-disabled-by-htmx")) {
          disabledElement.removeAttribute("disabled");
          disabledElement.removeAttribute("data-disabled-by-htmx");
        }
      });
    }
    function haveSeenNode(processed, elt) {
      for (let i = 0; i < processed.length; i++) {
        const node = processed[i];
        if (node.isSameNode(elt)) {
          return true;
        }
      }
      return false;
    }
    function shouldInclude(element) {
      const elt = (
        /** @type {HTMLInputElement} */
        element
      );
      if (elt.name === "" || elt.name == null || elt.disabled || closest(elt, "fieldset[disabled]")) {
        return false;
      }
      if (elt.type === "button" || elt.type === "submit" || elt.tagName === "image" || elt.tagName === "reset" || elt.tagName === "file") {
        return false;
      }
      if (elt.type === "checkbox" || elt.type === "radio") {
        return elt.checked;
      }
      return true;
    }
    function addValueToFormData(name, value, formData) {
      if (name != null && value != null) {
        if (Array.isArray(value)) {
          value.forEach(function(v) {
            formData.append(name, v);
          });
        } else {
          formData.append(name, value);
        }
      }
    }
    function removeValueFromFormData(name, value, formData) {
      if (name != null && value != null) {
        let values = formData.getAll(name);
        if (Array.isArray(value)) {
          values = values.filter((v) => value.indexOf(v) < 0);
        } else {
          values = values.filter((v) => v !== value);
        }
        formData.delete(name);
        forEach(values, (v) => formData.append(name, v));
      }
    }
    function getValueFromInput(elt) {
      if (elt instanceof HTMLSelectElement && elt.multiple) {
        return toArray(elt.querySelectorAll("option:checked")).map(function(e) {
          return (
            /** @type HTMLOptionElement */
            e.value
          );
        });
      }
      if (elt instanceof HTMLInputElement && elt.files) {
        return toArray(elt.files);
      }
      return elt.value;
    }
    function processInputValue(processed, formData, errors, elt, validate) {
      if (elt == null || haveSeenNode(processed, elt)) {
        return;
      } else {
        processed.push(elt);
      }
      if (shouldInclude(elt)) {
        const name = getRawAttribute(elt, "name");
        addValueToFormData(name, getValueFromInput(elt), formData);
        if (validate) {
          validateElement(elt, errors);
        }
      }
      if (elt instanceof HTMLFormElement) {
        forEach(elt.elements, function(input) {
          if (processed.indexOf(input) >= 0) {
            removeValueFromFormData(input.name, getValueFromInput(input), formData);
          } else {
            processed.push(input);
          }
          if (validate) {
            validateElement(input, errors);
          }
        });
        new FormData(elt).forEach(function(value, name) {
          if (value instanceof File && value.name === "") {
            return;
          }
          addValueToFormData(name, value, formData);
        });
      }
    }
    function validateElement(elt, errors) {
      const element = (
        /** @type {HTMLElement & ElementInternals} */
        elt
      );
      if (element.willValidate) {
        triggerEvent(element, "htmx:validation:validate");
        if (!element.checkValidity()) {
          if (triggerEvent(element, "htmx:validation:failed", {
            message: element.validationMessage,
            validity: element.validity
          }) && !errors.length && htmx.config.reportValidityOfForms) {
            element.reportValidity();
          }
          errors.push({ elt: element, message: element.validationMessage, validity: element.validity });
        }
      }
    }
    function overrideFormData(receiver, donor) {
      for (const key of donor.keys()) {
        receiver.delete(key);
      }
      donor.forEach(function(value, key) {
        receiver.append(key, value);
      });
      return receiver;
    }
    function getInputValues(elt, verb) {
      const processed = [];
      const formData = new FormData();
      const priorityFormData = new FormData();
      const errors = [];
      const internalData = getInternalData(elt);
      if (internalData.lastButtonClicked && !bodyContains(internalData.lastButtonClicked)) {
        internalData.lastButtonClicked = null;
      }
      let validate = elt instanceof HTMLFormElement && elt.noValidate !== true || getAttributeValue(elt, "hx-validate") === "true";
      if (internalData.lastButtonClicked) {
        validate = validate && internalData.lastButtonClicked.formNoValidate !== true;
      }
      if (verb !== "get") {
        processInputValue(processed, priorityFormData, errors, getRelatedForm(elt), validate);
      }
      processInputValue(processed, formData, errors, elt, validate);
      if (internalData.lastButtonClicked || elt.tagName === "BUTTON" || elt.tagName === "INPUT" && getRawAttribute(elt, "type") === "submit") {
        const button = internalData.lastButtonClicked || /** @type HTMLInputElement|HTMLButtonElement */
        elt;
        const name = getRawAttribute(button, "name");
        addValueToFormData(name, button.value, priorityFormData);
      }
      const includes = findAttributeTargets(elt, "hx-include");
      forEach(includes, function(node) {
        processInputValue(processed, formData, errors, asElement(node), validate);
        if (!matches(node, "form")) {
          forEach(asParentNode(node).querySelectorAll(INPUT_SELECTOR), function(descendant) {
            processInputValue(processed, formData, errors, descendant, validate);
          });
        }
      });
      overrideFormData(formData, priorityFormData);
      return { errors, formData, values: formDataProxy(formData) };
    }
    function appendParam(returnStr, name, realValue) {
      if (returnStr !== "") {
        returnStr += "&";
      }
      if (String(realValue) === "[object Object]") {
        realValue = JSON.stringify(realValue);
      }
      const s = encodeURIComponent(realValue);
      returnStr += encodeURIComponent(name) + "=" + s;
      return returnStr;
    }
    function urlEncode(values) {
      values = formDataFromObject(values);
      let returnStr = "";
      values.forEach(function(value, key) {
        returnStr = appendParam(returnStr, key, value);
      });
      return returnStr;
    }
    function getHeaders(elt, target, prompt2) {
      const headers = {
        "HX-Request": "true",
        "HX-Trigger": getRawAttribute(elt, "id"),
        "HX-Trigger-Name": getRawAttribute(elt, "name"),
        "HX-Target": getAttributeValue(target, "id"),
        "HX-Current-URL": location.href
      };
      getValuesForElement(elt, "hx-headers", false, headers);
      if (prompt2 !== void 0) {
        headers["HX-Prompt"] = prompt2;
      }
      if (getInternalData(elt).boosted) {
        headers["HX-Boosted"] = "true";
      }
      return headers;
    }
    function filterValues(inputValues, elt) {
      const paramsValue = getClosestAttributeValue(elt, "hx-params");
      if (paramsValue) {
        if (paramsValue === "none") {
          return new FormData();
        } else if (paramsValue === "*") {
          return inputValues;
        } else if (paramsValue.indexOf("not ") === 0) {
          forEach(paramsValue.slice(4).split(","), function(name) {
            name = name.trim();
            inputValues.delete(name);
          });
          return inputValues;
        } else {
          const newValues = new FormData();
          forEach(paramsValue.split(","), function(name) {
            name = name.trim();
            if (inputValues.has(name)) {
              inputValues.getAll(name).forEach(function(value) {
                newValues.append(name, value);
              });
            }
          });
          return newValues;
        }
      } else {
        return inputValues;
      }
    }
    function isAnchorLink(elt) {
      return !!getRawAttribute(elt, "href") && getRawAttribute(elt, "href").indexOf("#") >= 0;
    }
    function getSwapSpecification(elt, swapInfoOverride) {
      const swapInfo = swapInfoOverride || getClosestAttributeValue(elt, "hx-swap");
      const swapSpec = {
        swapStyle: getInternalData(elt).boosted ? "innerHTML" : htmx.config.defaultSwapStyle,
        swapDelay: htmx.config.defaultSwapDelay,
        settleDelay: htmx.config.defaultSettleDelay
      };
      if (htmx.config.scrollIntoViewOnBoost && getInternalData(elt).boosted && !isAnchorLink(elt)) {
        swapSpec.show = "top";
      }
      if (swapInfo) {
        const split = splitOnWhitespace(swapInfo);
        if (split.length > 0) {
          for (let i = 0; i < split.length; i++) {
            const value = split[i];
            if (value.indexOf("swap:") === 0) {
              swapSpec.swapDelay = parseInterval(value.slice(5));
            } else if (value.indexOf("settle:") === 0) {
              swapSpec.settleDelay = parseInterval(value.slice(7));
            } else if (value.indexOf("transition:") === 0) {
              swapSpec.transition = value.slice(11) === "true";
            } else if (value.indexOf("ignoreTitle:") === 0) {
              swapSpec.ignoreTitle = value.slice(12) === "true";
            } else if (value.indexOf("scroll:") === 0) {
              const scrollSpec = value.slice(7);
              var splitSpec = scrollSpec.split(":");
              const scrollVal = splitSpec.pop();
              var selectorVal = splitSpec.length > 0 ? splitSpec.join(":") : null;
              swapSpec.scroll = scrollVal;
              swapSpec.scrollTarget = selectorVal;
            } else if (value.indexOf("show:") === 0) {
              const showSpec = value.slice(5);
              var splitSpec = showSpec.split(":");
              const showVal = splitSpec.pop();
              var selectorVal = splitSpec.length > 0 ? splitSpec.join(":") : null;
              swapSpec.show = showVal;
              swapSpec.showTarget = selectorVal;
            } else if (value.indexOf("focus-scroll:") === 0) {
              const focusScrollVal = value.slice("focus-scroll:".length);
              swapSpec.focusScroll = focusScrollVal == "true";
            } else if (i == 0) {
              swapSpec.swapStyle = value;
            } else {
              logError("Unknown modifier in hx-swap: " + value);
            }
          }
        }
      }
      return swapSpec;
    }
    function usesFormData(elt) {
      return getClosestAttributeValue(elt, "hx-encoding") === "multipart/form-data" || matches(elt, "form") && getRawAttribute(elt, "enctype") === "multipart/form-data";
    }
    function encodeParamsForBody(xhr, elt, filteredParameters) {
      let encodedParameters = null;
      withExtensions(elt, function(extension) {
        if (encodedParameters == null) {
          encodedParameters = extension.encodeParameters(xhr, filteredParameters, elt);
        }
      });
      if (encodedParameters != null) {
        return encodedParameters;
      } else {
        if (usesFormData(elt)) {
          return overrideFormData(new FormData(), formDataFromObject(filteredParameters));
        } else {
          return urlEncode(filteredParameters);
        }
      }
    }
    function makeSettleInfo(target) {
      return { tasks: [], elts: [target] };
    }
    function updateScrollState(content, swapSpec) {
      const first = content[0];
      const last = content[content.length - 1];
      if (swapSpec.scroll) {
        var target = null;
        if (swapSpec.scrollTarget) {
          target = asElement(querySelectorExt(first, swapSpec.scrollTarget));
        }
        if (swapSpec.scroll === "top" && (first || target)) {
          target = target || first;
          target.scrollTop = 0;
        }
        if (swapSpec.scroll === "bottom" && (last || target)) {
          target = target || last;
          target.scrollTop = target.scrollHeight;
        }
        if (typeof swapSpec.scroll === "number") {
          getWindow().setTimeout(function() {
            window.scrollTo(
              0,
              /** @type number */
              swapSpec.scroll
            );
          }, 0);
        }
      }
      if (swapSpec.show) {
        var target = null;
        if (swapSpec.showTarget) {
          let targetStr = swapSpec.showTarget;
          if (swapSpec.showTarget === "window") {
            targetStr = "body";
          }
          target = asElement(querySelectorExt(first, targetStr));
        }
        if (swapSpec.show === "top" && (first || target)) {
          target = target || first;
          target.scrollIntoView({ block: "start", behavior: htmx.config.scrollBehavior });
        }
        if (swapSpec.show === "bottom" && (last || target)) {
          target = target || last;
          target.scrollIntoView({ block: "end", behavior: htmx.config.scrollBehavior });
        }
      }
    }
    function getValuesForElement(elt, attr, evalAsDefault, values, event) {
      if (values == null) {
        values = {};
      }
      if (elt == null) {
        return values;
      }
      const attributeValue = getAttributeValue(elt, attr);
      if (attributeValue) {
        let str2 = attributeValue.trim();
        let evaluateValue = evalAsDefault;
        if (str2 === "unset") {
          return null;
        }
        if (str2.indexOf("javascript:") === 0) {
          str2 = str2.slice(11);
          evaluateValue = true;
        } else if (str2.indexOf("js:") === 0) {
          str2 = str2.slice(3);
          evaluateValue = true;
        }
        if (str2.indexOf("{") !== 0) {
          str2 = "{" + str2 + "}";
        }
        let varsValues;
        if (evaluateValue) {
          varsValues = maybeEval(elt, function() {
            if (event) {
              return Function("event", "return (" + str2 + ")").call(elt, event);
            } else {
              return Function("return (" + str2 + ")").call(elt);
            }
          }, {});
        } else {
          varsValues = parseJSON(str2);
        }
        for (const key in varsValues) {
          if (varsValues.hasOwnProperty(key)) {
            if (values[key] == null) {
              values[key] = varsValues[key];
            }
          }
        }
      }
      return getValuesForElement(asElement(parentElt(elt)), attr, evalAsDefault, values, event);
    }
    function maybeEval(elt, toEval, defaultVal) {
      if (htmx.config.allowEval) {
        return toEval();
      } else {
        triggerErrorEvent(elt, "htmx:evalDisallowedError");
        return defaultVal;
      }
    }
    function getHXVarsForElement(elt, event, expressionVars) {
      return getValuesForElement(elt, "hx-vars", true, expressionVars, event);
    }
    function getHXValsForElement(elt, event, expressionVars) {
      return getValuesForElement(elt, "hx-vals", false, expressionVars, event);
    }
    function getExpressionVars(elt, event) {
      return mergeObjects(getHXVarsForElement(elt, event), getHXValsForElement(elt, event));
    }
    function safelySetHeaderValue(xhr, header, headerValue) {
      if (headerValue !== null) {
        try {
          xhr.setRequestHeader(header, headerValue);
        } catch (e) {
          xhr.setRequestHeader(header, encodeURIComponent(headerValue));
          xhr.setRequestHeader(header + "-URI-AutoEncoded", "true");
        }
      }
    }
    function getPathFromResponse(xhr) {
      if (xhr.responseURL) {
        try {
          const url = new URL(xhr.responseURL);
          return url.pathname + url.search;
        } catch (e) {
          triggerErrorEvent(getDocument().body, "htmx:badResponseUrl", { url: xhr.responseURL });
        }
      }
    }
    function hasHeader(xhr, regexp) {
      return regexp.test(xhr.getAllResponseHeaders());
    }
    function ajaxHelper(verb, path, context) {
      verb = /** @type HttpVerb */
      verb.toLowerCase();
      if (context) {
        if (context instanceof Element || typeof context === "string") {
          return issueAjaxRequest(verb, path, null, null, {
            targetOverride: resolveTarget(context) || DUMMY_ELT,
            returnPromise: true
          });
        } else {
          let resolvedTarget = resolveTarget(context.target);
          if (context.target && !resolvedTarget || context.source && !resolvedTarget && !resolveTarget(context.source)) {
            resolvedTarget = DUMMY_ELT;
          }
          return issueAjaxRequest(
            verb,
            path,
            resolveTarget(context.source),
            context.event,
            {
              handler: context.handler,
              headers: context.headers,
              values: context.values,
              targetOverride: resolvedTarget,
              swapOverride: context.swap,
              select: context.select,
              returnPromise: true,
              push: context.push,
              replace: context.replace,
              selectOOB: context.selectOOB
            }
          );
        }
      } else {
        return issueAjaxRequest(verb, path, null, null, {
          returnPromise: true
        });
      }
    }
    function hierarchyForElt(elt) {
      const arr = [];
      while (elt) {
        arr.push(elt);
        elt = elt.parentElement;
      }
      return arr;
    }
    function verifyPath(elt, path, requestConfig) {
      const url = new URL(path, location.protocol !== "about:" ? location.href : window.origin);
      const origin = location.protocol !== "about:" ? location.origin : window.origin;
      const sameHost = origin === url.origin;
      if (htmx.config.selfRequestsOnly) {
        if (!sameHost) {
          return false;
        }
      }
      return triggerEvent(elt, "htmx:validateUrl", mergeObjects({ url, sameHost }, requestConfig));
    }
    function formDataFromObject(obj) {
      if (obj instanceof FormData) return obj;
      const formData = new FormData();
      for (const key in obj) {
        if (obj.hasOwnProperty(key)) {
          if (obj[key] && typeof obj[key].forEach === "function") {
            obj[key].forEach(function(v) {
              formData.append(key, v);
            });
          } else if (typeof obj[key] === "object" && !(obj[key] instanceof Blob)) {
            formData.append(key, JSON.stringify(obj[key]));
          } else {
            formData.append(key, obj[key]);
          }
        }
      }
      return formData;
    }
    function formDataArrayProxy(formData, name, array) {
      return new Proxy(array, {
        get: function(target, key) {
          if (typeof key === "number") return target[key];
          if (key === "length") return target.length;
          if (key === "push") {
            return function(value) {
              target.push(value);
              formData.append(name, value);
            };
          }
          if (typeof target[key] === "function") {
            return function() {
              target[key].apply(target, arguments);
              formData.delete(name);
              target.forEach(function(v) {
                formData.append(name, v);
              });
            };
          }
          if (target[key] && target[key].length === 1) {
            return target[key][0];
          } else {
            return target[key];
          }
        },
        set: function(target, index, value) {
          target[index] = value;
          formData.delete(name);
          target.forEach(function(v) {
            formData.append(name, v);
          });
          return true;
        }
      });
    }
    function formDataProxy(formData) {
      return new Proxy(formData, {
        get: function(target, name) {
          if (typeof name === "symbol") {
            const result = Reflect.get(target, name);
            if (typeof result === "function") {
              return function() {
                return result.apply(formData, arguments);
              };
            } else {
              return result;
            }
          }
          if (name === "toJSON") {
            return () => Object.fromEntries(formData);
          }
          if (name in target) {
            if (typeof target[name] === "function") {
              return function() {
                return formData[name].apply(formData, arguments);
              };
            }
          }
          const array = formData.getAll(name);
          if (array.length === 0) {
            return void 0;
          } else if (array.length === 1) {
            return array[0];
          } else {
            return formDataArrayProxy(target, name, array);
          }
        },
        set: function(target, name, value) {
          if (typeof name !== "string") {
            return false;
          }
          target.delete(name);
          if (value && typeof value.forEach === "function") {
            value.forEach(function(v) {
              target.append(name, v);
            });
          } else if (typeof value === "object" && !(value instanceof Blob)) {
            target.append(name, JSON.stringify(value));
          } else {
            target.append(name, value);
          }
          return true;
        },
        deleteProperty: function(target, name) {
          if (typeof name === "string") {
            target.delete(name);
          }
          return true;
        },
        // Support Object.assign call from proxy
        ownKeys: function(target) {
          return Reflect.ownKeys(Object.fromEntries(target));
        },
        getOwnPropertyDescriptor: function(target, prop) {
          return Reflect.getOwnPropertyDescriptor(Object.fromEntries(target), prop);
        }
      });
    }
    function issueAjaxRequest(verb, path, elt, event, etc, confirmed) {
      let resolve = null;
      let reject = null;
      etc = etc != null ? etc : {};
      if (etc.returnPromise && typeof Promise !== "undefined") {
        var promise = new Promise(function(_resolve, _reject) {
          resolve = _resolve;
          reject = _reject;
        });
      }
      if (elt == null) {
        elt = getDocument().body;
      }
      const responseHandler = etc.handler || handleAjaxResponse;
      const select = etc.select || null;
      if (!bodyContains(elt)) {
        maybeCall(resolve);
        return promise;
      }
      const target = etc.targetOverride || asElement(getTarget(elt));
      if (target == null || target == DUMMY_ELT) {
        triggerErrorEvent(elt, "htmx:targetError", { target: getClosestAttributeValue(elt, "hx-target") });
        maybeCall(reject);
        return promise;
      }
      let eltData = getInternalData(elt);
      const submitter = eltData.lastButtonClicked;
      if (submitter) {
        const buttonPath = getRawAttribute(submitter, "formaction");
        if (buttonPath != null) {
          path = buttonPath;
        }
        const buttonVerb = getRawAttribute(submitter, "formmethod");
        if (buttonVerb != null) {
          if (VERBS.includes(buttonVerb.toLowerCase())) {
            verb = /** @type HttpVerb */
            buttonVerb;
          } else {
            maybeCall(resolve);
            return promise;
          }
        }
      }
      const confirmQuestion = getClosestAttributeValue(elt, "hx-confirm");
      if (confirmed === void 0) {
        const issueRequest = function(skipConfirmation) {
          return issueAjaxRequest(verb, path, elt, event, etc, !!skipConfirmation);
        };
        const confirmDetails = { target, elt, path, verb, triggeringEvent: event, etc, issueRequest, question: confirmQuestion };
        if (triggerEvent(elt, "htmx:confirm", confirmDetails) === false) {
          maybeCall(resolve);
          return promise;
        }
      }
      let syncElt = elt;
      let syncStrategy = getClosestAttributeValue(elt, "hx-sync");
      let queueStrategy = null;
      let abortable = false;
      if (syncStrategy) {
        const syncStrings = syncStrategy.split(":");
        const selector = syncStrings[0].trim();
        if (selector === "this") {
          syncElt = findThisElement(elt, "hx-sync");
        } else {
          syncElt = asElement(querySelectorExt(elt, selector));
        }
        syncStrategy = (syncStrings[1] || "drop").trim();
        eltData = getInternalData(syncElt);
        if (syncStrategy === "drop" && eltData.xhr && eltData.abortable !== true) {
          maybeCall(resolve);
          return promise;
        } else if (syncStrategy === "abort") {
          if (eltData.xhr) {
            maybeCall(resolve);
            return promise;
          } else {
            abortable = true;
          }
        } else if (syncStrategy === "replace") {
          triggerEvent(syncElt, "htmx:abort");
        } else if (syncStrategy.indexOf("queue") === 0) {
          const queueStrArray = syncStrategy.split(" ");
          queueStrategy = (queueStrArray[1] || "last").trim();
        }
      }
      if (eltData.xhr) {
        if (eltData.abortable) {
          triggerEvent(syncElt, "htmx:abort");
        } else {
          if (queueStrategy == null) {
            if (event) {
              const eventData = getInternalData(event);
              if (eventData && eventData.triggerSpec && eventData.triggerSpec.queue) {
                queueStrategy = eventData.triggerSpec.queue;
              }
            }
            if (queueStrategy == null) {
              queueStrategy = "last";
            }
          }
          if (eltData.queuedRequests == null) {
            eltData.queuedRequests = [];
          }
          if (queueStrategy === "first" && eltData.queuedRequests.length === 0) {
            eltData.queuedRequests.push(function() {
              issueAjaxRequest(verb, path, elt, event, etc);
            });
          } else if (queueStrategy === "all") {
            eltData.queuedRequests.push(function() {
              issueAjaxRequest(verb, path, elt, event, etc);
            });
          } else if (queueStrategy === "last") {
            eltData.queuedRequests = [];
            eltData.queuedRequests.push(function() {
              issueAjaxRequest(verb, path, elt, event, etc);
            });
          }
          maybeCall(resolve);
          return promise;
        }
      }
      const xhr = new XMLHttpRequest();
      eltData.xhr = xhr;
      eltData.abortable = abortable;
      const endRequestLock = function() {
        eltData.xhr = null;
        eltData.abortable = false;
        if (eltData.queuedRequests != null && eltData.queuedRequests.length > 0) {
          const queuedRequest = eltData.queuedRequests.shift();
          queuedRequest();
        }
      };
      const promptQuestion = getClosestAttributeValue(elt, "hx-prompt");
      if (promptQuestion) {
        var promptResponse = prompt(promptQuestion);
        if (promptResponse === null || !triggerEvent(elt, "htmx:prompt", { prompt: promptResponse, target })) {
          maybeCall(resolve);
          endRequestLock();
          return promise;
        }
      }
      if (confirmQuestion && !confirmed) {
        if (!confirm(confirmQuestion)) {
          maybeCall(resolve);
          endRequestLock();
          return promise;
        }
      }
      let headers = getHeaders(elt, target, promptResponse);
      if (verb !== "get" && !usesFormData(elt)) {
        headers["Content-Type"] = "application/x-www-form-urlencoded";
      }
      if (etc.headers) {
        headers = mergeObjects(headers, etc.headers);
      }
      const results = getInputValues(elt, verb);
      let errors = results.errors;
      const rawFormData = results.formData;
      if (etc.values) {
        overrideFormData(rawFormData, formDataFromObject(etc.values));
      }
      const expressionVars = formDataFromObject(getExpressionVars(elt, event));
      const allFormData = overrideFormData(rawFormData, expressionVars);
      let filteredFormData = filterValues(allFormData, elt);
      if (htmx.config.getCacheBusterParam && verb === "get") {
        filteredFormData.set("org.htmx.cache-buster", getRawAttribute(target, "id") || "true");
      }
      if (path == null || path === "") {
        path = location.href;
      }
      const requestAttrValues = getValuesForElement(elt, "hx-request");
      const eltIsBoosted = getInternalData(elt).boosted;
      let useUrlParams = htmx.config.methodsThatUseUrlParams.indexOf(verb) >= 0;
      const requestConfig = {
        boosted: eltIsBoosted,
        useUrlParams,
        formData: filteredFormData,
        parameters: formDataProxy(filteredFormData),
        unfilteredFormData: allFormData,
        unfilteredParameters: formDataProxy(allFormData),
        headers,
        elt,
        target,
        verb,
        errors,
        withCredentials: etc.credentials || requestAttrValues.credentials || htmx.config.withCredentials,
        timeout: etc.timeout || requestAttrValues.timeout || htmx.config.timeout,
        path,
        triggeringEvent: event
      };
      if (!triggerEvent(elt, "htmx:configRequest", requestConfig)) {
        maybeCall(resolve);
        endRequestLock();
        return promise;
      }
      path = requestConfig.path;
      verb = requestConfig.verb;
      headers = requestConfig.headers;
      filteredFormData = formDataFromObject(requestConfig.parameters);
      errors = requestConfig.errors;
      useUrlParams = requestConfig.useUrlParams;
      if (errors && errors.length > 0) {
        triggerEvent(elt, "htmx:validation:halted", requestConfig);
        maybeCall(resolve);
        endRequestLock();
        return promise;
      }
      const splitPath = path.split("#");
      const pathNoAnchor = splitPath[0];
      const anchor = splitPath[1];
      let finalPath = path;
      if (useUrlParams) {
        finalPath = pathNoAnchor;
        const hasValues = !filteredFormData.keys().next().done;
        if (hasValues) {
          if (finalPath.indexOf("?") < 0) {
            finalPath += "?";
          } else {
            finalPath += "&";
          }
          finalPath += urlEncode(filteredFormData);
          if (anchor) {
            finalPath += "#" + anchor;
          }
        }
      }
      if (!verifyPath(elt, finalPath, requestConfig)) {
        triggerErrorEvent(elt, "htmx:invalidPath", requestConfig);
        maybeCall(reject);
        endRequestLock();
        return promise;
      }
      xhr.open(verb.toUpperCase(), finalPath, true);
      xhr.overrideMimeType("text/html");
      xhr.withCredentials = requestConfig.withCredentials;
      xhr.timeout = requestConfig.timeout;
      if (requestAttrValues.noHeaders) {
      } else {
        for (const header in headers) {
          if (headers.hasOwnProperty(header)) {
            const headerValue = headers[header];
            safelySetHeaderValue(xhr, header, headerValue);
          }
        }
      }
      const responseInfo = {
        xhr,
        target,
        requestConfig,
        etc,
        boosted: eltIsBoosted,
        select,
        pathInfo: {
          requestPath: path,
          finalRequestPath: finalPath,
          responsePath: null,
          anchor
        }
      };
      xhr.onload = function() {
        try {
          const hierarchy = hierarchyForElt(elt);
          responseInfo.pathInfo.responsePath = getPathFromResponse(xhr);
          responseHandler(elt, responseInfo);
          if (responseInfo.keepIndicators !== true) {
            removeRequestIndicators(indicators, disableElts);
          }
          triggerEvent(elt, "htmx:afterRequest", responseInfo);
          triggerEvent(elt, "htmx:afterOnLoad", responseInfo);
          if (!bodyContains(elt)) {
            let secondaryTriggerElt = null;
            while (hierarchy.length > 0 && secondaryTriggerElt == null) {
              const parentEltInHierarchy = hierarchy.shift();
              if (bodyContains(parentEltInHierarchy)) {
                secondaryTriggerElt = parentEltInHierarchy;
              }
            }
            if (secondaryTriggerElt) {
              triggerEvent(secondaryTriggerElt, "htmx:afterRequest", responseInfo);
              triggerEvent(secondaryTriggerElt, "htmx:afterOnLoad", responseInfo);
            }
          }
          maybeCall(resolve);
        } catch (e) {
          triggerErrorEvent(elt, "htmx:onLoadError", mergeObjects({ error: e }, responseInfo));
          throw e;
        } finally {
          endRequestLock();
        }
      };
      xhr.onerror = function() {
        removeRequestIndicators(indicators, disableElts);
        triggerErrorEvent(elt, "htmx:afterRequest", responseInfo);
        triggerErrorEvent(elt, "htmx:sendError", responseInfo);
        maybeCall(reject);
        endRequestLock();
      };
      xhr.onabort = function() {
        removeRequestIndicators(indicators, disableElts);
        triggerErrorEvent(elt, "htmx:afterRequest", responseInfo);
        triggerErrorEvent(elt, "htmx:sendAbort", responseInfo);
        maybeCall(reject);
        endRequestLock();
      };
      xhr.ontimeout = function() {
        removeRequestIndicators(indicators, disableElts);
        triggerErrorEvent(elt, "htmx:afterRequest", responseInfo);
        triggerErrorEvent(elt, "htmx:timeout", responseInfo);
        maybeCall(reject);
        endRequestLock();
      };
      if (!triggerEvent(elt, "htmx:beforeRequest", responseInfo)) {
        maybeCall(resolve);
        endRequestLock();
        return promise;
      }
      var indicators = addRequestIndicatorClasses(elt);
      var disableElts = disableElements(elt);
      forEach(["loadstart", "loadend", "progress", "abort"], function(eventName) {
        forEach([xhr, xhr.upload], function(target2) {
          target2.addEventListener(eventName, function(event2) {
            triggerEvent(elt, "htmx:xhr:" + eventName, {
              lengthComputable: event2.lengthComputable,
              loaded: event2.loaded,
              total: event2.total
            });
          });
        });
      });
      triggerEvent(elt, "htmx:beforeSend", responseInfo);
      const params = useUrlParams ? null : encodeParamsForBody(xhr, elt, filteredFormData);
      xhr.send(params);
      return promise;
    }
    function determineHistoryUpdates(elt, responseInfo) {
      const xhr = responseInfo.xhr;
      let pathFromHeaders = null;
      let typeFromHeaders = null;
      if (hasHeader(xhr, /HX-Push:/i)) {
        pathFromHeaders = xhr.getResponseHeader("HX-Push");
        typeFromHeaders = "push";
      } else if (hasHeader(xhr, /HX-Push-Url:/i)) {
        pathFromHeaders = xhr.getResponseHeader("HX-Push-Url");
        typeFromHeaders = "push";
      } else if (hasHeader(xhr, /HX-Replace-Url:/i)) {
        pathFromHeaders = xhr.getResponseHeader("HX-Replace-Url");
        typeFromHeaders = "replace";
      }
      if (pathFromHeaders) {
        if (pathFromHeaders === "false") {
          return {};
        } else {
          return {
            type: typeFromHeaders,
            path: pathFromHeaders
          };
        }
      }
      const requestPath = responseInfo.pathInfo.finalRequestPath;
      const responsePath = responseInfo.pathInfo.responsePath;
      let pushUrl = responseInfo.etc.push || getClosestAttributeValue(elt, "hx-push-url");
      let replaceUrl = responseInfo.etc.replace || getClosestAttributeValue(elt, "hx-replace-url");
      if (pushUrl === "false") pushUrl = null;
      if (replaceUrl === "false") replaceUrl = null;
      const elementIsBoosted = getInternalData(elt).boosted;
      let saveType = null;
      let path = null;
      if (pushUrl) {
        saveType = "push";
        path = pushUrl;
      } else if (replaceUrl) {
        saveType = "replace";
        path = replaceUrl;
      } else if (elementIsBoosted) {
        saveType = "push";
        path = responsePath || requestPath;
      }
      if (path) {
        if (path === "true") {
          path = responsePath || requestPath;
        }
        if (responseInfo.pathInfo.anchor && path.indexOf("#") === -1) {
          path = path + "#" + responseInfo.pathInfo.anchor;
        }
        return {
          type: saveType,
          path
        };
      } else {
        return {};
      }
    }
    function codeMatches(responseHandlingConfig, status) {
      var regExp = new RegExp(responseHandlingConfig.code);
      return regExp.test(status.toString(10));
    }
    function resolveResponseHandling(xhr) {
      for (var i = 0; i < htmx.config.responseHandling.length; i++) {
        var responseHandlingElement = htmx.config.responseHandling[i];
        if (codeMatches(responseHandlingElement, xhr.status)) {
          return responseHandlingElement;
        }
      }
      return {
        swap: false
      };
    }
    function handleTitle(title) {
      if (title) {
        const titleElt = find("title");
        if (titleElt) {
          titleElt.textContent = title;
        } else {
          window.document.title = title;
        }
      }
    }
    function resolveRetarget(elt, target) {
      if (target === "this") {
        return elt;
      }
      const resolvedTarget = asElement(querySelectorExt(elt, target));
      if (resolvedTarget == null) {
        triggerErrorEvent(elt, "htmx:targetError", { target });
        throw new Error(`Invalid re-target ${target}`);
      }
      return resolvedTarget;
    }
    function handleAjaxResponse(elt, responseInfo) {
      const xhr = responseInfo.xhr;
      let target = responseInfo.target;
      const etc = responseInfo.etc;
      const responseInfoSelect = responseInfo.select;
      if (!triggerEvent(elt, "htmx:beforeOnLoad", responseInfo)) return;
      if (hasHeader(xhr, /HX-Trigger:/i)) {
        handleTriggerHeader(xhr, "HX-Trigger", elt);
      }
      if (hasHeader(xhr, /HX-Location:/i)) {
        let redirectPath = xhr.getResponseHeader("HX-Location");
        var redirectSwapSpec = {};
        if (redirectPath.indexOf("{") === 0) {
          redirectSwapSpec = parseJSON(redirectPath);
          redirectPath = redirectSwapSpec.path;
          delete redirectSwapSpec.path;
        }
        redirectSwapSpec.push = redirectSwapSpec.push ?? "true";
        ajaxHelper("get", redirectPath, redirectSwapSpec);
        return;
      }
      const shouldRefresh = hasHeader(xhr, /HX-Refresh:/i) && xhr.getResponseHeader("HX-Refresh") === "true";
      if (hasHeader(xhr, /HX-Redirect:/i)) {
        responseInfo.keepIndicators = true;
        htmx.location.href = xhr.getResponseHeader("HX-Redirect");
        shouldRefresh && htmx.location.reload();
        return;
      }
      if (shouldRefresh) {
        responseInfo.keepIndicators = true;
        htmx.location.reload();
        return;
      }
      const historyUpdate = determineHistoryUpdates(elt, responseInfo);
      const responseHandling = resolveResponseHandling(xhr);
      const shouldSwap = responseHandling.swap;
      let isError = !!responseHandling.error;
      let ignoreTitle = htmx.config.ignoreTitle || responseHandling.ignoreTitle;
      let selectOverride = responseHandling.select;
      if (responseHandling.target) {
        responseInfo.target = resolveRetarget(elt, responseHandling.target);
      }
      var swapOverride = etc.swapOverride;
      if (swapOverride == null && responseHandling.swapOverride) {
        swapOverride = responseHandling.swapOverride;
      }
      if (hasHeader(xhr, /HX-Retarget:/i)) {
        responseInfo.target = resolveRetarget(elt, xhr.getResponseHeader("HX-Retarget"));
      }
      if (hasHeader(xhr, /HX-Reswap:/i)) {
        swapOverride = xhr.getResponseHeader("HX-Reswap");
      }
      var serverResponse = xhr.response;
      var beforeSwapDetails = mergeObjects({
        shouldSwap,
        serverResponse,
        isError,
        ignoreTitle,
        selectOverride,
        swapOverride
      }, responseInfo);
      if (responseHandling.event && !triggerEvent(target, responseHandling.event, beforeSwapDetails)) return;
      if (!triggerEvent(target, "htmx:beforeSwap", beforeSwapDetails)) return;
      target = beforeSwapDetails.target;
      serverResponse = beforeSwapDetails.serverResponse;
      isError = beforeSwapDetails.isError;
      ignoreTitle = beforeSwapDetails.ignoreTitle;
      selectOverride = beforeSwapDetails.selectOverride;
      swapOverride = beforeSwapDetails.swapOverride;
      responseInfo.target = target;
      responseInfo.failed = isError;
      responseInfo.successful = !isError;
      if (beforeSwapDetails.shouldSwap) {
        if (xhr.status === 286) {
          cancelPolling(elt);
        }
        withExtensions(elt, function(extension) {
          serverResponse = extension.transformResponse(serverResponse, xhr, elt);
        });
        if (historyUpdate.type) {
          saveCurrentPageToHistory();
        }
        var swapSpec = getSwapSpecification(elt, swapOverride);
        if (!swapSpec.hasOwnProperty("ignoreTitle")) {
          swapSpec.ignoreTitle = ignoreTitle;
        }
        addClassToElement(target, htmx.config.swappingClass);
        if (responseInfoSelect) {
          selectOverride = responseInfoSelect;
        }
        if (hasHeader(xhr, /HX-Reselect:/i)) {
          selectOverride = xhr.getResponseHeader("HX-Reselect");
        }
        const selectOOB = etc.selectOOB || getClosestAttributeValue(elt, "hx-select-oob");
        const select = getClosestAttributeValue(elt, "hx-select");
        swap(target, serverResponse, swapSpec, {
          select: selectOverride === "unset" ? null : selectOverride || select,
          selectOOB,
          eventInfo: responseInfo,
          anchor: responseInfo.pathInfo.anchor,
          contextElement: elt,
          afterSwapCallback: function() {
            if (hasHeader(xhr, /HX-Trigger-After-Swap:/i)) {
              let finalElt = elt;
              if (!bodyContains(elt)) {
                finalElt = getDocument().body;
              }
              handleTriggerHeader(xhr, "HX-Trigger-After-Swap", finalElt);
            }
          },
          afterSettleCallback: function() {
            if (hasHeader(xhr, /HX-Trigger-After-Settle:/i)) {
              let finalElt = elt;
              if (!bodyContains(elt)) {
                finalElt = getDocument().body;
              }
              handleTriggerHeader(xhr, "HX-Trigger-After-Settle", finalElt);
            }
          },
          beforeSwapCallback: function() {
            if (historyUpdate.type) {
              triggerEvent(getDocument().body, "htmx:beforeHistoryUpdate", mergeObjects({ history: historyUpdate }, responseInfo));
              if (historyUpdate.type === "push") {
                pushUrlIntoHistory(historyUpdate.path);
                triggerEvent(getDocument().body, "htmx:pushedIntoHistory", { path: historyUpdate.path });
              } else {
                replaceUrlInHistory(historyUpdate.path);
                triggerEvent(getDocument().body, "htmx:replacedInHistory", { path: historyUpdate.path });
              }
            }
          }
        });
      }
      if (isError) {
        triggerErrorEvent(elt, "htmx:responseError", mergeObjects({ error: "Response Status Error Code " + xhr.status + " from " + responseInfo.pathInfo.requestPath }, responseInfo));
      }
    }
    const extensions = {};
    function extensionBase() {
      return {
        init: function(api) {
          return null;
        },
        getSelectors: function() {
          return null;
        },
        onEvent: function(name, evt) {
          return true;
        },
        transformResponse: function(text, xhr, elt) {
          return text;
        },
        isInlineSwap: function(swapStyle) {
          return false;
        },
        handleSwap: function(swapStyle, target, fragment, settleInfo) {
          return false;
        },
        encodeParameters: function(xhr, parameters, elt) {
          return null;
        }
      };
    }
    function defineExtension(name, extension) {
      if (extension.init) {
        extension.init(internalAPI);
      }
      extensions[name] = mergeObjects(extensionBase(), extension);
    }
    function removeExtension(name) {
      delete extensions[name];
    }
    function getExtensions(elt, extensionsToReturn, extensionsToIgnore) {
      if (extensionsToReturn == void 0) {
        extensionsToReturn = [];
      }
      if (elt == void 0) {
        return extensionsToReturn;
      }
      if (extensionsToIgnore == void 0) {
        extensionsToIgnore = [];
      }
      const extensionsForElement = getAttributeValue(elt, "hx-ext");
      if (extensionsForElement) {
        forEach(extensionsForElement.split(","), function(extensionName) {
          extensionName = extensionName.replace(/ /g, "");
          if (extensionName.slice(0, 7) == "ignore:") {
            extensionsToIgnore.push(extensionName.slice(7));
            return;
          }
          if (extensionsToIgnore.indexOf(extensionName) < 0) {
            const extension = extensions[extensionName];
            if (extension && extensionsToReturn.indexOf(extension) < 0) {
              extensionsToReturn.push(extension);
            }
          }
        });
      }
      return getExtensions(asElement(parentElt(elt)), extensionsToReturn, extensionsToIgnore);
    }
    var isReady = false;
    getDocument().addEventListener("DOMContentLoaded", function() {
      isReady = true;
    });
    function ready(fn) {
      if (isReady || getDocument().readyState === "complete") {
        fn();
      } else {
        getDocument().addEventListener("DOMContentLoaded", fn);
      }
    }
    function insertIndicatorStyles() {
      if (htmx.config.includeIndicatorStyles !== false) {
        const nonceAttribute = htmx.config.inlineStyleNonce ? ` nonce="${htmx.config.inlineStyleNonce}"` : "";
        const indicator = htmx.config.indicatorClass;
        const request = htmx.config.requestClass;
        getDocument().head.insertAdjacentHTML(
          "beforeend",
          `<style${nonceAttribute}>.${indicator}{opacity:0;visibility: hidden} .${request} .${indicator}, .${request}.${indicator}{opacity:1;visibility: visible;transition: opacity 200ms ease-in}</style>`
        );
      }
    }
    function getMetaConfig() {
      const element = getDocument().querySelector('meta[name="htmx-config"]');
      if (element) {
        return parseJSON(element.content);
      } else {
        return null;
      }
    }
    function mergeMetaConfig() {
      const metaConfig = getMetaConfig();
      if (metaConfig) {
        htmx.config = mergeObjects(htmx.config, metaConfig);
      }
    }
    ready(function() {
      mergeMetaConfig();
      insertIndicatorStyles();
      let body = getDocument().body;
      processNode(body);
      const restoredElts = getDocument().querySelectorAll(
        "[hx-trigger='restored'],[data-hx-trigger='restored']"
      );
      body.addEventListener("htmx:abort", function(evt) {
        const target = (
          /** @type {CustomEvent} */
          evt.detail.elt || evt.target
        );
        const internalData = getInternalData(target);
        if (internalData && internalData.xhr) {
          internalData.xhr.abort();
        }
      });
      const originalPopstate = window.onpopstate ? window.onpopstate.bind(window) : null;
      window.onpopstate = function(event) {
        if (event.state && event.state.htmx) {
          restoreHistory();
          forEach(restoredElts, function(elt) {
            triggerEvent(elt, "htmx:restored", {
              document: getDocument(),
              triggerEvent
            });
          });
        } else {
          if (originalPopstate) {
            originalPopstate(event);
          }
        }
      };
      getWindow().setTimeout(function() {
        triggerEvent(body, "htmx:load", {});
        body = null;
      }, 0);
    });
    return htmx;
  })();
  var htmx_esm_default = htmx2;

  // src/typescript/htmx.ts
  globalThis.htmx = htmx_esm_default;

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
      const url = `${location.origin}/invoice/inv/createCreditConfirm`;
      const btn = querySelector(this.confirmButtonSelector);
      const absoluteUrl = new URL(location.href);
      if (btn) {
        btn.innerHTML = '<h6 class="text-center"><span class="spinner-border spinner-border-sm" role="status"></span></h6>';
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
          btn.innerHTML = '<h2 class="text-center"><i class="bi bi-x-lg"></i></h2>';
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
    globalThis.location.reload();
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
      button.innerHTML = '<h6 class="text-center"><span class="spinner-border spinner-border-sm" role="status"></span></h6>';
      button.disabled = true;
    } else {
      button.innerHTML = originalHtml || '<h6 class="text-center"><i class="bi bi-check-lg"></i></h6>';
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
      const statusItem = target.closest(".quote-status-item");
      if (statusItem) {
        event.preventDefault();
        void this.handleChangeStatus(statusItem);
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
        const url = `${location.origin}/invoice/quote/deleteItem/${encodeURIComponent(id)}`;
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
          delMulti.innerHTML = '<h2 class="text-center"><i class="bi bi-check-lg"></i></h2>';
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
        placeholder.innerHTML = '<h2 class="text-center"><span class="spinner-border spinner-border-sm" role="status"></span></h2>';
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
        placeholder.innerHTML = '<h2 class="text-center"><span class="spinner-border spinner-border-sm" role="status"></span></h2>';
        const response = await fetch(url, { cache: "no-store", credentials: "same-origin" });
        const html = await response.text();
        secureInsertHTML(placeholder, html);
      } catch (error) {
        console.error("Failed to load add-a-client modal", error);
      }
    }
    async handleQuoteCreateConfirm() {
      const url = `${location.origin}/invoice/quote/createConfirm`;
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
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check-lg"></i></h2>';
          secureReload();
        } else if (data.success === 0) {
          if (btn) btn.innerHTML = '<h6 class="text-center"><i class="bi bi-check-lg"></i></h6>';
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
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check-lg"></i></h2>';
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
      const url = `${location.origin}/invoice/quote/quoteToInvoiceConfirm`;
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
        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check-lg"></i></h2>';
        if (data.success && data.redirect_url) {
          globalThis.location.href = data.redirect_url;
        } else if (data.success && data.new_invoice_id) {
          globalThis.location.href = `${location.origin}/invoice/inv/view/${data.new_invoice_id}`;
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
      const url = `${location.origin}/invoice/quote/quoteToSoConfirm`;
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
        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check-lg"></i></h2>';
        if (data.success && data.redirect_url) {
          globalThis.location.href = data.redirect_url;
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
      const url = `${location.origin}/invoice/quote/quoteToQuoteConfirm`;
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
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check-lg"></i></h2>';
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
    async handleChangeStatus(item) {
      const statusId = item.dataset["statusId"];
      if (!statusId) return;
      const table = document.getElementById("table-quote");
      if (!table) return;
      const checkboxes = table.querySelectorAll(
        'input[type="checkbox"]:checked'
      );
      const keylist = [];
      checkboxes.forEach((cb) => {
        if (cb.id) keylist.push(cb.id);
      });
      if (keylist.length === 0) return;
      const url = new URL(`${location.origin}/invoice/quote/changeStatus`);
      url.searchParams.set("status_id", statusId);
      keylist.forEach((id) => url.searchParams.append("keylist[]", id));
      try {
        const response = await getJson(url.toString(), {});
        const data = parsedata(response);
        if (data.success === 1) {
          secureReload();
        }
      } catch (error) {
        console.error("quote/changeStatus error", error);
      }
    }
    handlePdfGeneration(target) {
      if (target.closest("#quote_to_pdf_confirm_with_custom_fields")) {
        const url = `${location.origin}/invoice/quote/pdf/1`;
        globalThis.open(url, "_blank");
        return;
      }
      if (target.closest("#quote_to_pdf_confirm_without_custom_fields")) {
        const url = `${location.origin}/invoice/quote/pdf/0`;
        globalThis.open(url, "_blank");
        return;
      }
    }
    async handleClientNoteSave(event) {
      const target = event.target;
      const saveBtn = target.closest("#save_client_note");
      if (!saveBtn) return;
      const url = `${location.origin}/invoice/client/saveClientNoteNew`;
      const loadUrl = `${location.origin}/invoice/client/loadClientNotes`;
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
      const url = `${location.origin}/invoice/quote/saveQuoteTaxRate`;
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
      if (target.classList?.contains("taggable")) {
        globalThis.lastTaggableClicked = target;
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
      if (typeof globalThis.bootstrap?.Tooltip !== "undefined") {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
          try {
            new globalThis.bootstrap.Tooltip(element);
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
          if (globalThis.lastTaggableClicked) {
            this.insertAtCaret(globalThis.lastTaggableClicked.id, currentTarget.value);
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
    globalThis.location.reload();
  }
  function createSecureUIElement(type = "h6", className = "text-center", iconClass = "spinner-border spinner-border-sm") {
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
      button.appendChild(createSecureUIElement("h6", "text-center", "spinner-border spinner-border-sm"));
      button.disabled = true;
    } else {
      if (originalHtml) {
        button.textContent = "";
        button.appendChild(createSecureUIElement("h6", "text-center", "bi bi-check-lg"));
      } else {
        button.textContent = "";
        button.appendChild(createSecureUIElement("h6", "text-center", "bi bi-check-lg"));
      }
      button.disabled = false;
    }
  }
  function setSecureButtonContent(btn, type = "h6", className = "text-center", iconClass = "spinner-border spinner-border-sm") {
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
      const url = `${location.origin}/invoice/client/createConfirm`;
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
            setSecureButtonContent(btn, "h2", "text-center", "bi bi-check-lg");
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
      const url = `${location.origin}/invoice/client/saveClientNoteNew`;
      const btn = document.querySelector(".save_client_note") || saveNoteBtn;
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
          globalThis.location.reload();
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
      const url = `${location.origin}/invoice/client/deleteClientNote`;
      deleteBtn.textContent = "";
      const spinner = document.createElement("i");
      spinner.className = "spinner-border spinner-border-sm";
      deleteBtn.appendChild(spinner);
      deleteBtn.disabled = true;
      try {
        const response = await getJson(url, { note_id: noteId });
        const data = parsedata(response);
        if (data.success === 1) {
          const notePanel = deleteBtn.closest(".panel");
          if (notePanel) {
            notePanel.remove();
          }
        } else {
          deleteBtn.disabled = false;
          alert(data.message || "Failed to delete note. Please try again.");
        }
      } catch (error) {
        console.error("Delete client note error:", error);
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
        checkIcon.className = "bi bi-check-lg";
        submitButton.appendChild(checkIcon);
      }
      const modal = document.getElementById("modal-add-quote") || document.getElementById("modal-add-client");
      if (modal) {
        const bootstrapModal = globalThis.bootstrap?.Modal?.getInstance(modal);
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
        checkIcon.className = "bi bi-check-lg";
        submitButton.appendChild(checkIcon);
      }
      const modal = document.getElementById("modal-add-inv") || document.getElementById("modal-add-client");
      if (modal) {
        const bootstrapModal = globalThis.bootstrap?.Modal?.getInstance(modal);
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
      button.innerHTML = '<h2 class="text-center"><span class="spinner-border spinner-border-sm" role="status"></span></h2>';
      button.disabled = true;
    } else {
      button.innerHTML = originalHtml || '<h2 class="text-center"><i class="bi bi-check-lg"></i></h2>';
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
        const url = `${location.origin}/invoice/inv/markAsSent`;
        const response = await getJson(url, { keylist: selected });
        const data = parsedata(response);
        if (data.success === 1) {
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check-lg"></i></h2>';
          globalThis.location.reload();
        } else {
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-x-lg"></i></h2>';
          globalThis.location.reload();
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
        const url = `${location.origin}/invoice/inv/markSentAsDraft`;
        const response = await getJson(url, { keylist: selected });
        const data = parsedata(response);
        if (data.success === 1) {
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check-lg"></i></h2>';
          globalThis.location.reload();
        } else {
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-x-lg"></i></h2>';
          globalThis.location.reload();
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
        btn.innerHTML = '<h6 class="text-center"><span class="spinner-border spinner-border-sm" role="status"></span></h6>';
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
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check-lg"></i></h2>';
          this.closeModal("create-recurring-multiple");
          setTimeout(() => {
            globalThis.location.reload();
          }, 500);
        } else {
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-x-lg"></i></h2>';
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
        btn.innerHTML = '<h2 class="text-center"><span class="spinner-border spinner-border-sm" role="status"></span></h2>';
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
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check-lg"></i></h2>';
          globalThis.location.reload();
        } else {
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-x-lg"></i></h2>';
          globalThis.location.reload();
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
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
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
        const url = `${location.origin}/invoice/inv/saveInvTaxRate`;
        const response = await getJson(url, payload);
        const data = parsedata(response);
        if (data.success === 1) {
          if (btn) btn.innerHTML = '<i class="bi bi-check-lg"></i>';
          this.closeModal("add-inv-tax");
          setTimeout(() => {
            globalThis.location.reload();
          }, 500);
        } else {
          if (btn) btn.innerHTML = '<i class="bi bi-x-lg"></i>';
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
        btn.innerHTML = '<h6 class="text-center"><span class="spinner-border spinner-border-sm" role="status"></span></h6>';
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
        const url = `${location.origin}/invoice/inv/invToInvConfirm`;
        const response = await getJson(url, payload);
        const data = parsedata(response);
        if (data.success === 1) {
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check-lg"></i></h2>';
          if (data.new_invoice_id) {
            globalThis.location.href = `${location.origin}/invoice/inv/view/${data.new_invoice_id}`;
          } else {
            globalThis.location.reload();
          }
        } else {
          if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-x-lg"></i></h2>';
          globalThis.location.reload();
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
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
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
          if (btn) btn.innerHTML = '<i class="bi bi-check-lg"></i>';
          this.closeModal("delete-items");
          setTimeout(() => {
            globalThis.location.reload();
          }, 500);
        } else {
          if (btn) btn.innerHTML = '<i class="bi bi-x-lg"></i>';
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
      globalThis.open(url, "_blank");
    }
    handleModalPdfView(withCustomFields) {
      const endpoint = withCustomFields ? "1" : "0";
      const url = `${location.origin}/invoice/inv/pdf/${endpoint}`;
      const iframe = document.getElementById("modal-view-inv-pdf");
      if (iframe) {
        iframe.src = url;
      }
      try {
        if (typeof globalThis.bootstrap?.Modal !== "undefined") {
          const modalEl = document.getElementById("modal-layout-modal-pdf-inv");
          if (modalEl) {
            const modal = new globalThis.bootstrap.Modal(modalEl);
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
      globalThis.open(url, "_blank");
    }
    async handlePaymentSubmit() {
      const url = `${location.origin}/invoice/payment/add_with_ajax`;
      const btn = document.getElementById("btn_modal_payment_submit");
      const originalHtml = btn?.innerHTML;
      if (btn) {
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
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
          if (btn) btn.innerHTML = '<i class="bi bi-check-lg"></i>';
          this.closeModal("payment-modal");
          setTimeout(() => {
            globalThis.location.reload();
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
        const url = `${location.origin}/invoice/inv/deleteItem/${itemId}`;
        const response = await getJson(url, { id: itemId });
        const data = parsedata(response);
        if (data.success === 1) {
          const itemRow = deleteItem.closest(".item");
          if (itemRow) {
            itemRow.remove();
          }
          alert("Deleted");
          globalThis.location.reload();
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
        if (typeof globalThis.bootstrap?.Modal !== "undefined") {
          const modalEl = document.getElementById(modalId);
          if (modalEl) {
            const modalInstance = globalThis.bootstrap.Modal.getInstance(modalEl);
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
  var BUTTON_ICONS = {
    loading: "spinner-border spinner-border-sm",
    success: "bi bi-check-lg",
    error: "bi bi-x-lg"
  };
  function setButtonState(buttons, state) {
    buttons.forEach((button) => {
      button.textContent = "";
      const h6 = document.createElement("h6");
      h6.className = "text-center";
      const i = document.createElement("i");
      i.className = BUTTON_ICONS[state];
      h6.appendChild(i);
      button.appendChild(h6);
    });
  }
  var ProductHandler = class {
    constructor() {
      this.bindEventListeners();
      this.exposeGlobalFunctions();
      this.initializeComponents();
      this.bindModalEvents();
    }
    bindModalEvents() {
      document.addEventListener("shown.bs.modal", (event) => {
        const target = event.target;
        if (target && target.id === "modal-choose-items") {
          this.updateButtonStates();
        }
      });
    }
    bindEventListeners() {
      document.addEventListener("click", this.handleClick.bind(this), true);
      document.addEventListener("change", this.handleChange.bind(this), true);
      document.addEventListener("keydown", this.handleKeydown.bind(this), true);
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
      if (target.id === "filter-button-inv" || target.closest("#filter-button-inv")) {
        event.preventDefault();
        this.filterProducts("inv");
        return;
      }
      if (target.id === "filter-button-quote" || target.closest("#filter-button-quote")) {
        event.preventDefault();
        this.filterProducts("quote");
        return;
      }
      if (target.id === "product-reset-button-inv" || target.closest("#product-reset-button-inv")) {
        event.preventDefault();
        this.resetProducts("inv");
        return;
      }
      if (target.id === "product-reset-button-quote" || target.closest("#product-reset-button-quote")) {
        event.preventDefault();
        this.resetProducts("quote");
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
    handleChange(event) {
      const target = event.target;
      if (target.id === "filter_family_inv") {
        this.filterProducts("inv");
      }
      if (target.id === "filter_family_quote") {
        this.filterProducts("quote");
      }
    }
    handleKeydown(event) {
      if (event.key === "Enter") {
        const target = event.target;
        if (target.id === "filter_product_inv") {
          event.preventDefault();
          this.filterProducts("inv");
        }
        if (target.id === "filter_product_quote") {
          event.preventDefault();
          this.filterProducts("quote");
        }
      }
    }
    initializeComponents() {
      if (typeof globalThis.TomSelect !== "undefined") {
        document.querySelectorAll(".simple-select").forEach((el) => {
          const tracked = el;
          if (!tracked._tomselect) {
            tracked._tomselect = new globalThis.TomSelect(el, {});
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
      event.preventDefault();
      const url = `${globalThis.location.origin}/invoice/product/search`;
      const buttons = document.querySelectorAll(
        ".product_filters_submit"
      );
      setButtonState(buttons, "loading");
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
          setButtonState(buttons, "success");
        } else {
          setButtonState(buttons, "error");
          if (data.message) {
            alert(data.message);
          }
        }
      } catch (error) {
        console.error("product search failed", error);
        setButtonState(buttons, "error");
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
      const absoluteUrl = new URL(globalThis.location.href);
      const btn = document.querySelector(".select-items-confirm-quote");
      this.setSecureButtonContent(btn, "h2", "text-center", "spinner-border spinner-border-sm");
      const productIds = [];
      const quoteId = (absoluteUrl.pathname.split("/").at(-1) || "").replace(/[^0-9]/g, "");
      document.querySelectorAll("input[name='product_ids[]']:checked").forEach((input) => {
        const value = parseInt(input.value, 10);
        if (!isNaN(value)) {
          productIds.push(value);
        }
      });
      const sortedProductIds = productIds.toSorted((a, b) => a - b);
      const urlParams = new URLSearchParams({ quote_id: quoteId });
      sortedProductIds.forEach((id) => urlParams.append("product_ids[]", String(id)));
      const url = `/invoice/product/selection_quote?${urlParams.toString()}`;
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
        this.setSecureButtonContent(btn, "h2", "text-center", "bi bi-check-lg");
        globalThis.location.reload();
      } catch (error) {
        console.error("Error:", error);
        this.setSecureButtonContent(btn, "h2", "text-center", "bi bi-x-lg");
      }
    }
    async handleInvoiceConfirm() {
      const absoluteUrl = new URL(globalThis.location.href);
      const btn = document.querySelector(".select-items-confirm-inv");
      this.setSecureButtonContent(btn, "h2", "text-center", "spinner-border spinner-border-sm");
      const productIds = [];
      const invId = absoluteUrl.pathname.split("/").at(-1) || "";
      document.querySelectorAll("input[name='product_ids[]']:checked").forEach((input) => {
        const value = parseInt(input.value, 10);
        if (!isNaN(value)) {
          productIds.push(value);
        }
      });
      const sortedProductIds = productIds.toSorted((a, b) => a - b);
      const urlParams = new URLSearchParams({ inv_id: invId });
      sortedProductIds.forEach((id) => urlParams.append("product_ids[]", String(id)));
      const url = `/invoice/product/selection_inv?${urlParams.toString()}`;
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
        this.setSecureButtonContent(btn, "h2", "text-center", "bi bi-check-lg");
        globalThis.location.reload();
      } catch (error) {
        console.error("Error:", error);
        this.setSecureButtonContent(btn, "h2", "text-center", "bi bi-x-lg");
      }
    }
    processProducts(products) {
      for (const [, product] of Object.entries(products)) {
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
      globalThis.productTableFilter = this.filterTableBySku.bind(this);
    }
    /**
     * Filter products by family and/or product name
     */
    async filterProducts(type) {
      const familySelect = document.getElementById(`filter_family_${type}`);
      const productInput = document.getElementById(`filter_product_${type}`);
      const productTable = document.getElementById("product-lookup-table");
      if (!productTable) return;
      const familyId = familySelect ? familySelect.value : "0";
      const productFilter = productInput ? productInput.value.trim() : "";
      this.setLoadingSpinner(productTable);
      const params = new URLSearchParams();
      if (familyId && familyId !== "0") {
        params.append("ff", familyId);
      }
      if (productFilter) {
        params.append("fp", productFilter);
      }
      const queryString = params.toString();
      const url = queryString ? `/invoice/product/lookup?${queryString}` : "/invoice/product/lookup";
      try {
        const response = await fetch(url, {
          method: "GET",
          headers: {
            "X-Requested-With": "XMLHttpRequest"
          },
          credentials: "same-origin",
          cache: "no-store"
        });
        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, "text/html");
        const fragment = document.createDocumentFragment();
        Array.from(doc.body.children).forEach((child) => fragment.appendChild(child));
        productTable.textContent = "";
        productTable.appendChild(fragment);
        this.updateButtonStates();
      } catch (error) {
        console.error("Error filtering products:", error);
        this.setTableError(productTable, "Error loading products");
      }
    }
    /**
     * Reset product filters and reload all products
     */
    async resetProducts(type) {
      const familySelect = document.getElementById(`filter_family_${type}`);
      const productInput = document.getElementById(`filter_product_${type}`);
      const productTable = document.getElementById("product-lookup-table");
      if (!productTable) return;
      if (familySelect) familySelect.value = "0";
      if (productInput) productInput.value = "";
      this.setLoadingSpinner(productTable);
      try {
        const response = await fetch("/invoice/product/lookup?rt=true", {
          method: "GET",
          headers: {
            "X-Requested-With": "XMLHttpRequest"
          },
          credentials: "same-origin",
          cache: "no-store"
        });
        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, "text/html");
        const fragment = document.createDocumentFragment();
        Array.from(doc.body.children).forEach((child) => fragment.appendChild(child));
        productTable.textContent = "";
        productTable.appendChild(fragment);
        this.updateButtonStates();
      } catch (error) {
        console.error("Error resetting products:", error);
        this.setTableError(productTable, "Error loading products");
      }
    }
    setLoadingSpinner(container) {
      container.textContent = "";
      const h2 = document.createElement("h2");
      h2.className = "text-center";
      const i = document.createElement("i");
      i.className = "spinner-border spinner-border-sm";
      h2.appendChild(i);
      container.appendChild(h2);
    }
    setTableError(container, message) {
      container.textContent = "";
      const p = document.createElement("p");
      p.className = "text-danger";
      p.textContent = message;
      container.appendChild(p);
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
      console.log(`\xF0\u0178\u201D\x8D Task button state update: ${checkboxes.length} total checkboxes, ${checkedBoxes.length} checked, anyChecked: ${anyChecked}`);
      let buttons;
      buttons = ctx.querySelectorAll(".select-items-confirm-task, .select-items-confirm-task-inv, .select-items-confirm-task-quote");
      if (buttons.length === 0) {
        buttons = document.querySelectorAll(".select-items-confirm-task, .select-items-confirm-task-inv, .select-items-confirm-task-quote");
      }
      if (buttons.length === 0) {
        const buttonById = document.getElementById("task-modal-submit") || document.getElementById("task-modal-submit-quote");
        if (buttonById) {
          buttons = [buttonById];
          console.log("\xF0\u0178\u201D\x8D Found button by ID fallback");
        }
      }
      if (buttons.length === 0) {
        const modals = document.querySelectorAll(".modal");
        modals.forEach((modal) => {
          const modalButtons = modal.querySelectorAll(".select-items-confirm-task, .select-items-confirm-task-inv, .select-items-confirm-task-quote, #task-modal-submit, #task-modal-submit-quote");
          if (modalButtons.length > 0) {
            buttons = modalButtons;
            console.log("\xF0\u0178\u201D\x8D Found button in modal fallback");
          }
        });
      }
      console.log(`\xF0\u0178\u201D\x8D Found ${buttons.length} task submit buttons`);
      buttons.forEach((btn) => {
        const button = btn;
        if (anyChecked) {
          button.removeAttribute("disabled");
          button.removeAttribute("aria-disabled");
          button.disabled = false;
          console.log("\xE2\u0153\u2026 Task submit button enabled");
        } else {
          button.setAttribute("disabled", "true");
          button.setAttribute("aria-disabled", "true");
          button.disabled = true;
          console.log("\xE2\x9D\u0152 Task submit button disabled");
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
      btn.innerHTML = '<h2 class="text-center"><span class="spinner-border spinner-border-sm" role="status"></span></h2>';
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
        btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check-lg"></i></h2>';
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
      tasksTable.innerHTML = '<h2 class="text-center"><span class="spinner-border spinner-border-sm" role="status"></span></h2>';
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
    globalThis.location.reload();
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
      const statusBtn = target.closest(".so-status-btn");
      if (statusBtn) {
        const statusId = parseInt(statusBtn.dataset.statusId ?? "0", 10);
        if (statusId > 0) {
          this.handleChangeStatus(statusId);
        }
        return;
      }
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
      if (typeof globalThis.TomSelect === "undefined") return;
      const selects = document.querySelectorAll(
        ".simple-select"
      );
      selects.forEach((element) => {
        if (!element._tomselect) {
          try {
            new globalThis.TomSelect(element, {});
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
        if (modalEl && globalThis.bootstrap?.Modal) {
          const modalInstance = new globalThis.bootstrap.Modal(modalEl);
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
      globalThis.location.reload();
      globalThis.open(url, "_blank");
    }
    /**
     * Handle Sales Order to Invoice conversion
     */
    async handleSoToInvoiceConversion() {
      const btn = document.querySelector(".so_to_invoice_confirm");
      if (btn) {
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>';
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
        const url = location.origin + "/invoice/salesorder/soToInvoiceConfirm";
        const response = await getJson(url, payload);
        if (response && response.success === 1) {
          if (btn) {
            btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check-lg"></i></h2>';
          }
          if (response.inv_id) {
            globalThis.location.href = `${location.origin}/invoice/inv/view/${response.inv_id}`;
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
            btn.innerHTML = '<h6 class="text-center"><i class="bi bi-check-lg"></i></h6>';
          }
        }
      } catch (error) {
        console.error("SO to Invoice conversion failed:", error);
        if (btn) {
          btn.innerHTML = '<h6 class="text-center"><i class="bi bi-check-lg"></i></h6>';
        }
        alert("An error occurred during conversion. Please try again.");
      }
    }
    /**
     * Collect IDs of checked rows from #table-salesorder
     */
    getCheckedSalesOrderIds() {
      const selected = [];
      const table = document.getElementById("table-salesorder");
      if (!table) return selected;
      const checkboxes = table.querySelectorAll(
        'input[type="checkbox"]:checked'
      );
      checkboxes.forEach((cb) => {
        if (cb.id) selected.push(cb.id);
      });
      return selected;
    }
    /**
     * Bulk-change the status of all checked sales orders.
     * Called by SalesOrderToolbar button clicks.
     */
    async handleChangeStatus(statusId) {
      const selected = this.getCheckedSalesOrderIds();
      if (selected.length === 0) {
        alert("Please select at least one sales order.");
        return;
      }
      const btn = document.getElementById(`btn-so-status-${statusId}`);
      const originalHtml = btn?.innerHTML;
      if (btn) {
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i>';
        btn.disabled = true;
      }
      try {
        const url = `${location.origin}/invoice/salesorder/changeStatus`;
        const response = await getJson(
          url,
          { keylist: selected, status_id: statusId }
        );
        const data = parsedata(response);
        if (data.success === 1) {
          globalThis.location.reload();
        } else {
          if (btn && originalHtml) {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
          }
        }
      } catch (error) {
        console.error("changeStatus error", error);
        if (btn && originalHtml) {
          btn.innerHTML = originalHtml;
          btn.disabled = false;
        }
        alert("An error occurred. See console for details.");
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
          globalThis.location.reload();
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
      if (target.closest("#btn-generate-products")) {
        event.preventDefault();
        event.stopPropagation();
        const checkedBoxes = document.querySelectorAll(
          'input[type="checkbox"][name="family_ids[]"]:checked'
        );
        if (checkedBoxes.length === 0) {
          alert("Please select at least one family to generate products.");
          return;
        }
        const modalEl = document.getElementById("generate-products-modal");
        if (modalEl && globalThis.bootstrap?.Modal !== void 0) {
          globalThis.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
      }
      if (target.closest("#process-generate-products")) {
        this.processProductGeneration();
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
     * Handler: when primary category changes, load secondary categories.
     * Guards against empty value — initializeSelectors fires this on page
     * load before the user makes a selection, which would produce a URL like
     * /family/secondaries/ that the {category_primary_id} route cannot match.
     */
    async onPrimaryChange() {
      const primarySelect = document.getElementById(
        "family-category-primary-id"
      );
      if (!primarySelect) return;
      const primaryCategoryId = primarySelect.value;
      if (!primaryCategoryId) {
        this.populateSelect(
          document.getElementById("family-category-secondary-id"),
          null,
          "None"
        );
        this.populateSelect(
          document.getElementById("family-name"),
          null,
          "None"
        );
        return;
      }
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
            secondaryDropdown.dispatchEvent(new Event("change", { bubbles: true }));
          }
        } else {
          this.populateSelect(
            document.getElementById("family-category-secondary-id"),
            null,
            "None"
          );
          this.populateSelect(
            document.getElementById("family-name"),
            null,
            "None"
          );
        }
      } catch (error) {
        console.error("Error loading secondary categories", error);
        this.populateSelect(
          document.getElementById("family-category-secondary-id"),
          null,
          "None"
        );
        this.populateSelect(
          document.getElementById("family-name"),
          null,
          "None"
        );
      }
    }
    /**
     * Handler: when secondary category changes, load family names.
     * Guards against empty value for the same reason as onPrimaryChange.
     */
    async onSecondaryChange() {
      const secondarySelect = document.getElementById(
        "family-category-secondary-id"
      );
      if (!secondarySelect) return;
      const secondaryCategoryId = secondarySelect.value;
      if (!secondaryCategoryId) {
        this.populateSelect(
          document.getElementById("family-name"),
          null,
          "None"
        );
        return;
      }
      const url = `${location.origin}/invoice/family/names/${encodeURIComponent(secondaryCategoryId)}`;
      try {
        const payload = {
          category_secondary_id: secondaryCategoryId
        };
        const response = await getJson(url, payload);
        const data = parsedata(response);
        if (data.success === 1) {
          const familyNames = data.family_names || {};
          this.populateSelect(
            document.getElementById("family-name"),
            familyNames,
            "None"
          );
        } else {
          this.populateSelect(
            document.getElementById("family-name"),
            null,
            "None"
          );
        }
      } catch (error) {
        console.error("Error loading family names", error);
        this.populateSelect(
          document.getElementById("family-name"),
          null,
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
      processBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Generating...';
      processBtn.disabled = true;
      try {
        const familyIds = selectedFamilies.map((f) => f.family_id);
        const csrfToken = document.querySelector('input[name="_csrf"]')?.value || "";
        const params = new URLSearchParams();
        familyIds.forEach((id) => params.append("family_ids[]", id));
        params.append("tax_rate_id", taxRateSelect.value);
        params.append("unit_id", unitSelect.value);
        params.append("_csrf", csrfToken);
        const response = await fetch(`${location.origin}/invoice/family/generateProducts`, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            "X-Requested-With": "XMLHttpRequest"
          },
          credentials: "same-origin",
          body: params.toString()
        });
        const rawText = await response.text();
        console.log("[generateProducts] HTTP status:", response.status);
        console.log("[generateProducts] raw response:", rawText);
        let data;
        try {
          const parsed = JSON.parse(rawText);
          data = typeof parsed === "string" ? JSON.parse(parsed) : parsed;
        } catch {
          alert("Server returned non-JSON response (HTTP " + response.status + "). Check PHP error log.");
          processBtn.innerHTML = originalText;
          processBtn.disabled = false;
          return;
        }
        if (data.success) {
          this.handleGenerationSuccess(data, processBtn);
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
    handleGenerationSuccess(data, processBtn) {
      processBtn.innerHTML = '<i class="bi bi-check-lg"></i> Success!';
      alert(data.message || `Successfully generated ${data.count || 0} products!`);
      if (data.warnings?.length) {
        console.warn("Warnings during product generation:", data.warnings);
      }
      const modal = document.getElementById("generate-products-modal");
      if (modal && globalThis.bootstrap?.Modal !== void 0) {
        const bsModal = globalThis.bootstrap.Modal.getInstance(modal);
        if (bsModal) {
          bsModal.hide();
        }
      }
      setTimeout(() => {
        if (data.redirect_url) {
          globalThis.location.href = data.redirect_url;
        } else {
          globalThis.location.reload();
        }
      }, 1e3);
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
    const bs = globalThis.bootstrap;
    if (!bs?.Tooltip) return;
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
      try {
        bs.Tooltip.getOrCreateInstance(el);
      } catch (e) {
      }
    });
  }
  function initSimpleSelects(root) {
    const TomSelect = globalThis.TomSelect;
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
      loaderIcon.classList.add("icon-spin");
      loaderIcon.classList.remove("text-danger");
    }
    setTimeout(() => {
      if (loaderError) loaderError.style.display = "block";
      if (loaderIcon) {
        loaderIcon.classList.remove("icon-spin");
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
      loaderIcon.classList.add("icon-spin");
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
    globalThis.toggleCommalistPicker = toggleCommalistPicker;
    globalThis.picker = null;
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
        globalThis.picker = picker;
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

  // src/typescript/payment-stripe.ts
  function initStripePayment() {
    const configEl = document.getElementById("stripe-payment-config");
    if (!configEl) return;
    const publishableKey = configEl.dataset.publishableKey ?? "";
    const clientSecret = configEl.dataset.clientSecret ?? "";
    const returnUrl = configEl.dataset.returnUrl ?? "";
    if (!publishableKey || !clientSecret) return;
    const stripe = Stripe(publishableKey);
    let elements;
    async function initialize() {
      elements = stripe.elements({ clientSecret });
      const paymentElement = elements.create("payment", { layout: "tabs" });
      paymentElement.mount("#payment-element");
    }
    async function handleSubmit(e) {
      e.preventDefault();
      setLoading(true);
      const { error } = await stripe.confirmPayment({
        elements,
        confirmParams: { return_url: returnUrl }
      });
      if (error.type === "card_error" || error.type === "validation_error") {
        showMessage(error.message ?? "An error occurred.");
      } else {
        showMessage("An unexpected error occurred.");
      }
      setLoading(false);
    }
    async function checkStatus() {
      const intentSecret = new URLSearchParams(globalThis.location.search).get(
        "payment_intent_client_secret"
      );
      if (!intentSecret) return;
      const { paymentIntent } = await stripe.retrievePaymentIntent(intentSecret);
      switch (paymentIntent.status) {
        case "succeeded":
          showMessage("Payment succeeded!");
          break;
        case "processing":
          showMessage("Your payment is processing.");
          break;
        case "requires_payment_method":
          showMessage("Your payment was not successful, please try again.");
          break;
        default:
          showMessage("Something went wrong.");
      }
    }
    function showMessage(messageText) {
      const messageContainer = document.querySelector("#payment-message");
      if (!messageContainer) return;
      messageContainer.classList.remove("hidden");
      messageContainer.textContent = messageText;
      setTimeout(() => {
        messageContainer.classList.add("hidden");
        messageContainer.textContent = "";
      }, 4e3);
    }
    function setLoading(isLoading) {
      const submitBtn = document.querySelector("#submit");
      const spinner = document.querySelector("#spinner");
      const buttonText = document.querySelector("#button-text");
      if (!submitBtn || !spinner || !buttonText) return;
      submitBtn.disabled = isLoading;
      if (isLoading) {
        spinner.classList.remove("hidden");
        buttonText.classList.add("hidden");
      } else {
        spinner.classList.add("hidden");
        buttonText.classList.remove("hidden");
      }
    }
    void initialize();
    void checkStatus();
    const form = document.querySelector("#payment-form");
    form?.addEventListener("submit", (e) => void handleSubmit(e));
  }

  // src/typescript/payment-amazon.ts
  function initAmazonPayment() {
    const configEl = document.getElementById("amazon-pay-config");
    if (!configEl) return;
    const merchantId = configEl.dataset.merchantId ?? "";
    const publicKeyId = configEl.dataset.publicKeyId ?? "";
    const ledgerCurrency = configEl.dataset.ledgerCurrency ?? "";
    const checkoutLanguage = configEl.dataset.checkoutLanguage ?? "";
    const productType = configEl.dataset.productType ?? "";
    const amount = configEl.dataset.amount ?? "";
    const payloadJSON = configEl.dataset.payloadJson ?? "";
    const signature = configEl.dataset.signature ?? "";
    if (!merchantId || !publicKeyId) return;
    amazon.Pay.renderButton("#AmazonPayButton", {
      merchantId,
      publicKeyId,
      ledgerCurrency,
      checkoutLanguage,
      productType,
      placement: "Other",
      buttonColor: "Gold",
      estimatedOrderAmount: { amount, currencyCode: ledgerCurrency },
      createCheckoutSessionConfig: { payloadJSON, signature }
    });
  }

  // src/typescript/payment-braintree.ts
  function initBraintreePayment() {
    const configEl = document.getElementById("braintree-config");
    if (!configEl) return;
    const clientToken = configEl.dataset.clientToken ?? "";
    if (!clientToken) return;
    const form = document.getElementById("payment-form");
    braintree.dropin.create(
      { authorization: clientToken, container: "#dropin-container" },
      (error, dropinInstance) => {
        if (error) {
          console.error("Braintree Drop-in error:", error);
          return;
        }
        if (!dropinInstance || !form) return;
        form.addEventListener("submit", (event) => {
          event.preventDefault();
          dropinInstance.requestPaymentMethod((err, payload) => {
            if (err) {
              console.error("Braintree requestPaymentMethod error:", err);
              return;
            }
            const nonceField = document.getElementById(
              "nonce"
            );
            if (nonceField && payload) {
              nonceField.value = payload.nonce;
            }
            form.submit();
          });
        });
      }
    );
  }

  // src/typescript/telegram-providers.ts
  function initTelegramProviderPopup() {
    const modal = document.getElementById("telegram-providers");
    if (modal && modal.parentElement !== document.body) {
      document.body.appendChild(modal);
    }
  }

  // src/typescript/family-street-order.ts
  function collectIds(list) {
    return Array.from(list.querySelectorAll("li[data-id]")).map((li) => parseInt(li.dataset["id"] ?? "0", 10)).filter((id) => id > 0);
  }
  function refreshPositionBadges(list) {
    list.querySelectorAll("li[data-id]").forEach((li, index) => {
      const badge = li.querySelector(".street-position");
      if (badge) badge.textContent = String(index + 1);
    });
  }
  function setStatus(el, message, type) {
    el.textContent = message;
    el.className = `alert alert-${type} py-1 px-2 mt-2`;
    el.style.display = "block";
  }
  async function postOrder(url, csrf, ids) {
    const body = new URLSearchParams();
    body.append("_csrf", csrf);
    ids.forEach((id) => body.append("order[]", String(id)));
    const response = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: body.toString()
    });
    if (!response.ok) {
      return { success: false, message: `HTTP ${response.status}` };
    }
    return response.json();
  }
  function initStreetOrder() {
    const list = document.getElementById("street-order-list");
    if (!list) return;
    const reorderUrl = list.dataset["reorderUrl"] ?? "";
    const csrfInput = document.getElementById("street-order-csrf");
    const statusEl = document.getElementById("street-order-status");
    if (!reorderUrl || !csrfInput || !statusEl) return;
    let dragged = null;
    let lastEnterTarget = null;
    list.addEventListener("dragstart", (e) => {
      const target = e.target.closest("li[data-id]");
      if (!target) return;
      dragged = target;
      lastEnterTarget = null;
      target.classList.add("opacity-50");
      e.dataTransfer?.setData("text/plain", target.dataset["id"] ?? "");
    });
    list.addEventListener("dragend", () => {
      dragged?.classList.remove("opacity-50");
      dragged = null;
      lastEnterTarget = null;
    });
    list.addEventListener("dragover", (e) => {
      e.preventDefault();
    });
    list.addEventListener("dragenter", (e) => {
      e.preventDefault();
      if (!dragged) return;
      const target = e.target.closest("li[data-id]");
      if (!target || target === dragged || target === lastEnterTarget) return;
      lastEnterTarget = target;
      const rect = target.getBoundingClientRect();
      const after = e.clientY > rect.top + rect.height / 2;
      if (after) {
        target.after(dragged);
      } else {
        target.before(dragged);
      }
      refreshPositionBadges(list);
    });
    list.addEventListener("drop", async (e) => {
      e.preventDefault();
      refreshPositionBadges(list);
      const ids = collectIds(list);
      setStatus(statusEl, "\u2026saving", "info");
      try {
        const result = await postOrder(reorderUrl, csrfInput.value, ids);
        if (result.success) {
          setStatus(statusEl, "\u2713 Order saved", "success");
        } else {
          setStatus(statusEl, `\u2717 ${result.message ?? "Save failed"}`, "danger");
        }
      } catch (err) {
        setStatus(statusEl, `\u2717 Network error: ${String(err)}`, "danger");
      }
    });
  }

  // src/typescript/google-translate-popover.ts
  function formatStepContent(raw) {
    const lines = raw.split(/\r?\n/);
    let html = "";
    let inList = false;
    for (const line of lines) {
      const trimmed = line.trim();
      if (!trimmed) continue;
      const stepMatch = /^---Step--(\d+):\s*(.*)/.exec(trimmed);
      if (stepMatch) {
        if (!inList) {
          html += '<ol class="mb-1 ps-3 small">';
          inList = true;
        }
        html += `<li><strong>Step ${stepMatch[1]}:</strong> ${stepMatch[2]}</li>`;
      } else {
        if (inList) {
          html += "</ol>";
          inList = false;
        }
        html += `<p class="mb-1 small">${trimmed}</p>`;
      }
    }
    if (inList) {
      html += "</ol>";
    }
    return html;
  }
  function initStepPopovers() {
    const bs = globalThis.bootstrap;
    if (!bs?.Popover) return;
    document.querySelectorAll("[data-popover-steps]").forEach((el) => {
      const rawContent = el.dataset.bsContent ?? "";
      delete el.dataset.bsContent;
      delete el.dataset.bsTrigger;
      try {
        bs.Popover.getOrCreateInstance(el, {
          html: true,
          content: formatStepContent(rawContent),
          trigger: "hover focus",
          placement: el.dataset.bsPlacement ?? "right",
          customClass: "popover-steps",
          // Append to body so the popover is never clipped by the
          // dropdown-menu's overflow context.
          container: "body",
          delay: { show: 150, hide: 300 }
        });
      } catch (e) {
        console.warn("Step popover init failed:", e);
      }
    });
  }

  // src/typescript/list-utils.ts
  var AmountMagnifier = class {
    constructor(tableId) {
      this.tableId = tableId;
      this.attachMagnifiers();
      this.setupObserver();
    }
    tableId;
    magnificationFactor = 1.4;
    animationDuration = 250;
    observer;
    attachMagnifiers() {
      [".badge.bg-success", ".badge.bg-warning", ".badge.bg-danger"].forEach((selector) => {
        document.querySelectorAll(selector).forEach((el) => {
          if (this.isAmount(el) && !el.dataset["magnifierInitialized"]) {
            this.addBehavior(el);
            el.dataset["magnifierInitialized"] = "true";
          }
        });
      });
    }
    isAmount(el) {
      const text = el.textContent?.trim() ?? "";
      if (text.length === 0 || text.length > 20) return false;
      return /^(?=([\d,]+))\1(?:\.\d+)?$/.test(text);
    }
    addBehavior(el) {
      let borderColor = "#007bff";
      let bgColor = "rgba(255, 255, 255, 0.95)";
      if (el.classList.contains("bg-success")) {
        borderColor = "#28a745";
        bgColor = "#d4edda";
      } else if (el.classList.contains("bg-warning")) {
        borderColor = "#ffc107";
        bgColor = "#fff3cd";
      } else if (el.classList.contains("bg-danger")) {
        borderColor = "#dc3545";
        bgColor = "#f8d7da";
      }
      const cs = globalThis.getComputedStyle(el);
      const orig = {
        fontSize: cs.fontSize,
        fontWeight: cs.fontWeight,
        backgroundColor: cs.backgroundColor,
        border: cs.border,
        borderRadius: cs.borderRadius,
        padding: cs.padding,
        zIndex: cs.zIndex,
        position: cs.position,
        transform: cs.transform,
        boxShadow: cs.boxShadow
      };
      el.style.transition = `all ${this.animationDuration}ms ease-in-out`;
      el.style.cursor = "pointer";
      el.classList.add("amount-magnifiable");
      let hovered = false;
      el.addEventListener("mouseenter", () => {
        if (!hovered) {
          hovered = true;
          this.magnify(el, orig, borderColor, bgColor);
        }
      });
      el.addEventListener("mouseleave", () => {
        if (hovered) {
          hovered = false;
          this.restore(el, orig);
        }
      });
      el.addEventListener("click", (e) => {
        e.preventDefault();
        if (hovered) {
          this.restore(el, orig);
          hovered = false;
        } else {
          this.magnify(el, orig, borderColor, bgColor);
          hovered = true;
        }
      });
    }
    magnify(el, orig, borderColor, bgColor) {
      const newSize = Number.parseFloat(orig.fontSize) * this.magnificationFactor;
      el.style.fontSize = `${newSize}px`;
      el.style.fontWeight = "bold";
      el.style.backgroundColor = bgColor;
      el.style.border = `2px solid ${borderColor}`;
      el.style.borderRadius = "6px";
      el.style.padding = "8px 12px";
      el.style.zIndex = "1000";
      el.style.position = "relative";
      el.style.transform = "scale(1.1)";
      el.style.boxShadow = "0 4px 12px rgba(0,0,0,0.15)";
    }
    restore(el, orig) {
      const style = el.style;
      Object.keys(orig).forEach((k) => {
        style[k] = orig[k];
      });
    }
    setupObserver() {
      this.observer = new MutationObserver((mutations) => {
        for (const m of mutations) {
          if (m.type === "childList" && m.addedNodes.length > 0) {
            setTimeout(() => this.attachMagnifiers(), 100);
            break;
          }
        }
      });
      const container = document.getElementById(this.tableId) ?? document.querySelector(".table-responsive");
      if (container) this.observer.observe(container, { childList: true, subtree: true });
    }
  };
  function initGroupBySelect() {
    const select = document.querySelector(".group-by-select");
    if (!select) return;
    const allowed = /* @__PURE__ */ new Set(["none", "status", "client", "client_group", "month", "year", "date", "amount_range"]);
    select.addEventListener("change", function() {
      if (allowed.has(this.value)) {
        const base = this.dataset["baseUrl"] ?? "";
        globalThis.location.href = `${base}?groupBy=${encodeURIComponent(this.value)}`;
      }
    });
  }
  function initGroupCollapsible() {
    globalThis["toggleGroupRows"] = (groupHeader) => {
      const icon = groupHeader.querySelector(".group-toggle-icon");
      if (!icon) return;
      const collapsing = icon.classList.contains("bi-chevron-down");
      icon.classList.toggle("bi-chevron-down", !collapsing);
      icon.classList.toggle("bi-chevron-right", collapsing);
      let next = groupHeader.nextElementSibling;
      while (next !== null && !next.classList.contains("group-header")) {
        next.style.display = collapsing ? "none" : "";
        next = next.nextElementSibling;
      }
    };
    globalThis["toggleAllGroups"] = (expand = null) => {
      document.querySelectorAll(".group-header").forEach((header) => {
        const icon = header.querySelector(".group-toggle-icon");
        const collapsed = icon?.classList.contains("bi-chevron-right") ?? false;
        const toggle = globalThis["toggleGroupRows"];
        if (expand === null || expand && collapsed || !expand && !collapsed) toggle(header);
      });
    };
  }

  // src/typescript/inv-index.ts
  var magnifier;
  var mobilePreview;
  var MobilePreviewToggle = class {
    isActive = false;
    previewWin = null;
    toggleBtn;
    sideTab;
    constructor() {
      this.injectStyles();
      this.toggleBtn = this.createButton();
      this.sideTab = this.createSideTab();
      this.watchPopup();
    }
    injectStyles() {
      if (document.getElementById("mp-styles")) return;
      const s = document.createElement("style");
      s.id = "mp-styles";
      s.textContent = ".mp-btn{position:fixed;bottom:72px;right:20px;z-index:10001;display:flex;align-items:center;gap:6px;padding:9px 14px 9px 18px;background:#212529;color:#fff;border:2px solid #495057;border-radius:22px;cursor:pointer;font-size:13px;font-weight:600;box-shadow:0 4px 14px rgba(0,0,0,.35);transition:background .2s,transform .15s;}.mp-btn:hover{background:#495057;transform:translateY(-2px);}.mp-btn.mp-on{background:#0d6efd;border-color:#0d6efd;}.mp-dismiss{display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;margin-left:2px;background:rgba(255,255,255,.15);border:none;border-radius:50%;color:#fff;font-size:14px;line-height:1;cursor:pointer;flex-shrink:0;padding:0;transition:background .15s;}.mp-dismiss:hover{background:rgba(255,255,255,.35);}.mp-side-tab{position:fixed;top:50%;left:0;z-index:10001;transform:translateY(-50%);width:28px;height:28px;padding:0;background:#212529;color:#fff;border:2px solid #495057;border-left:none;border-radius:0 8px 8px 0;cursor:pointer;font-size:15px;line-height:28px;text-align:center;box-shadow:3px 0 10px rgba(0,0,0,.3);transition:background .2s;display:none;}.mp-side-tab:hover{background:#495057;}.mp-side-tab.mp-visible{display:block;}";
      document.head.appendChild(s);
    }
    createButton() {
      const btn = document.createElement("button");
      btn.className = "mp-btn";
      btn.title = "Preview at Android 390 px width";
      const label = document.createElement("span");
      label.textContent = "\u{1F4F1} Mobile Preview";
      btn.appendChild(label);
      const dismiss = document.createElement("button");
      dismiss.className = "mp-dismiss";
      dismiss.title = "Collapse to left margin";
      dismiss.textContent = "\u2039";
      dismiss.addEventListener("click", (e) => {
        e.stopPropagation();
        this.collapse();
      });
      btn.appendChild(dismiss);
      btn.addEventListener("click", () => this.toggle());
      document.body.appendChild(btn);
      return btn;
    }
    createSideTab() {
      const tab = document.createElement("button");
      tab.className = "mp-side-tab";
      tab.title = "Restore Mobile Preview button";
      tab.textContent = "\u{1F4F1}";
      tab.addEventListener("click", () => this.restore());
      document.body.appendChild(tab);
      return tab;
    }
    collapse() {
      if (this.isActive) this.deactivate();
      this.toggleBtn.style.display = "none";
      this.sideTab.classList.add("mp-visible");
    }
    restore() {
      this.sideTab.classList.remove("mp-visible");
      this.toggleBtn.style.display = "";
    }
    activate() {
      this.isActive = true;
      const features = "width=390,height=844,resizable=yes,scrollbars=yes,location=no,menubar=no,toolbar=no,status=no";
      this.previewWin = globalThis.open(globalThis.location.href, "mp-preview", features) ?? null;
      const span = this.toggleBtn.querySelector("span");
      if (span) span.textContent = "\u{1F5A5}\uFE0F Close Preview";
      this.toggleBtn.classList.add("mp-on");
    }
    deactivate() {
      this.isActive = false;
      if (this.previewWin !== null && !this.previewWin.closed) this.previewWin.close();
      this.previewWin = null;
      const span = this.toggleBtn.querySelector("span");
      if (span) span.textContent = "\u{1F4F1} Mobile Preview";
      this.toggleBtn.classList.remove("mp-on");
    }
    toggle() {
      if (this.isActive) this.deactivate();
      else this.activate();
    }
    watchPopup() {
      setInterval(() => {
        if (this.isActive && this.previewWin?.closed === true) {
          this.isActive = false;
          this.previewWin = null;
          const span = this.toggleBtn.querySelector("span");
          if (span) span.textContent = "\u{1F4F1} Mobile Preview";
          this.toggleBtn.classList.remove("mp-on");
        }
      }, 800);
    }
  };
  function initInvIndex() {
    const setup = () => {
      const configEl = document.getElementById("inv-filter-config");
      const labels = configEl ? JSON.parse(configEl.textContent ?? "{}") : {};
      magnifier = new AmountMagnifier("table-invoice");
      initGroupBySelect();
      Object.entries(labels).forEach(([id, label]) => {
        const sel = document.getElementById(id);
        if (sel !== null && sel.options.length > 0) sel.options[0].text = label;
      });
      if (document.querySelector(".group-header") !== null) {
        initGroupCollapsible();
      }
      mobilePreview = new MobilePreviewToggle();
    };
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", setup);
    } else {
      setup();
    }
  }

  // src/typescript/quote-index.ts
  var magnifier2;
  function initQuoteIndex() {
    const setup = () => {
      magnifier2 = new AmountMagnifier("table-quote");
      initGroupBySelect();
      if (document.querySelector(".group-header") !== null) {
        initGroupCollapsible();
      }
    };
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", setup);
    } else {
      setup();
    }
  }

  // src/typescript/allowance-charge-toggle.ts
  var AllowanceChargeToggleHandler = class {
    constructor() {
      if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", () => this.#init());
      } else {
        this.#init();
      }
    }
    #init() {
      const select = document.getElementById("allowance_charge_id");
      if (!select) return;
      let templates;
      try {
        templates = JSON.parse(select.dataset["acTemplates"] ?? "{}");
      } catch {
        templates = {};
      }
      const baseRow = document.getElementById("row-base-amount");
      const baseInput = document.getElementById("base_amount_calc");
      const amountInput = document.getElementById("amount");
      const formulaHint = document.getElementById("amount-formula");
      if (!baseRow || !baseInput || !amountInput || !formulaHint) return;
      const getTemplate = () => templates[select.value] ?? { mfn: 0, base: 0 };
      const recalculate = () => {
        const t = getTemplate();
        if (t.mfn <= 0) return;
        const base = parseFloat(baseInput.value) || 0;
        const result = Math.round(t.mfn * base) / 100;
        amountInput.value = result.toFixed(2);
        formulaHint.textContent = `${t.mfn} \xD7 ${base} \xF7 100 = ${result.toFixed(2)}`;
      };
      const applyMode = () => {
        const t = getTemplate();
        const variable = t.mfn > 0;
        baseRow.style.display = variable ? "" : "none";
        if (variable) {
          if (!baseInput.dataset["userModified"]) {
            baseInput.value = String(t.base);
          }
          recalculate();
        } else {
          formulaHint.textContent = "";
        }
      };
      select.addEventListener("change", () => {
        baseInput.dataset["userModified"] = "";
        applyMode();
      });
      baseInput.addEventListener("input", () => {
        baseInput.dataset["userModified"] = "1";
        recalculate();
      });
      applyMode();
    }
  };

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
      new AllowanceChargeToggleHandler();
      console.log(
        "Invoice TypeScript App initialized with all core handlers: Quote, Client, Invoice, Product, Task, SalesOrder, Family, and Settings"
      );
    }
    /**
     * Initialize Bootstrap tooltips — Bootstrap must be loaded before this runs.
     * Registration order in layout/invoice.php ensures bootstrap.bundle.js
     * executes before the IIFE, so (globalThis as any).bootstrap is available here.
     */
    initializeTooltips() {
      const bs = globalThis.bootstrap;
      if (!bs?.Tooltip) return;
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
        try {
          bs.Tooltip.getOrCreateInstance(element);
        } catch (error) {
          console.warn("Tooltip initialization failed:", error);
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
            globalThis.lastTaggableClicked = target;
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
    document.addEventListener("DOMContentLoaded", () => {
      new InvoiceApp();
      initStripePayment();
      initAmazonPayment();
      initBraintreePayment();
      initTelegramProviderPopup();
      initStreetOrder();
      initStepPopovers();
    });
  } else {
    new InvoiceApp();
    initStripePayment();
    initAmazonPayment();
    initBraintreePayment();
    initTelegramProviderPopup();
    initStreetOrder();
    initStepPopovers();
  }
  return __toCommonJS(index_exports);
})();
//# sourceMappingURL=invoice-typescript-iife.js.map
