if (
    (!(function (t, e) {
        "use strict";
        "object" === typeof module && "object" === typeof module.exports
            ? (module.exports = t.document
                  ? e(t, !0)
                  : function (t) {
                        if (!t.document) throw new Error("jQuery requires a window with a document");
                        return e(t);
                    })
            : e(t);
    })("undefined" !== typeof window ? window : this, function (C, t) {
        "use strict";
        function g(t) {
            return null !== t && t === t.window;
        }
        var e = [],
            D = C.document,
            n = Object.getPrototypeOf,
            a = e.slice,
            m = e.concat,
            l = e.push,
            s = e.indexOf,
            i = {},
            o = i.toString,
            v = i.hasOwnProperty,
            r = v.toString,
            c = r.call(Object),
            y = {},
            b = function (t) {
                return "function" === typeof t && "number" !== typeof t.nodeType;
            },
            h = { type: !0, src: !0, nonce: !0, noModule: !0 };
        function w(t, e, i) {
            var n,
                s,
                o = (i = i || D).createElement("script");
            if (((o.text = t), e)) for (n in h) (s = e[n] || (e.getAttribute && e.getAttribute(n))) && o.setAttribute(n, s);
            i.head.appendChild(o).parentNode.removeChild(o);
        }
        function _(t) {
            return null === t ? t + "" : "object" === typeof t || "function" === typeof t ? i[o.call(t)] || "object" : typeof t;
        }
        var u = "3.4.1",
            k = function (t, e) {
                return new k.fn.init(t, e);
            },
            d = /^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g;
        function p(t) {
            var e = !!t && "length" in t && t.length,
                i = _(t);
            return !b(t) && !g(t) && ("array" === i || 0 === e || ("number" === typeof e && 0 < e && e - 1 in t));
        }
        (k.fn = k.prototype = {
            jquery: u,
            constructor: k,
            length: 0,
            toArray: function () {
                return a.call(this);
            },
            get: function (t) {
                return null === t ? a.call(this) : t < 0 ? this[t + this.length] : this[t];
            },
            pushStack: function (t) {
                var e = k.merge(this.constructor(), t);
                return (e.prevObject = this), e;
            },
            each: function (t) {
                return k.each(this, t);
            },
            map: function (i) {
                return this.pushStack(
                    k.map(this, function (t, e) {
                        return i.call(t, e, t);
                    })
                );
            },
            slice: function () {
                return this.pushStack(a.apply(this, arguments));
            },
            first: function () {
                return this.eq(0);
            },
            last: function () {
                return this.eq(-1);
            },
            eq: function (t) {
                var e = this.length,
                    i = +t + (t < 0 ? e : 0);
                return this.pushStack(0 <= i && i < e ? [this[i]] : []);
            },
            end: function () {
                return this.prevObject || this.constructor();
            },
            push: l,
            sort: e.sort,
            splice: e.splice
            
        }),
            (k.extend = k.fn.extend = function () {
                var t,
                    e,
                    i,
                    n,
                    s,
                    o,
                    r = arguments[0] || {},
                    a = 1,
                    l = arguments.length,
                    c = !1;
                for ("boolean" === typeof r && ((c = r), (r = arguments[a] || {}), a++), "object" === typeof r || b(r) || (r = {}), a === l && ((r = this), a--); a < l; a++)
                    if (null !==    (t = arguments[a]))
                        for (e in t)
                            (n = t[e]),
                                "__proto__" !== e &&
                                    r !== n &&
                                    (c && n && (k.isPlainObject(n) || (s = Array.isArray(n)))
                                        ? ((i = r[e]), (o = s && !Array.isArray(i) ? [] : s || k.isPlainObject(i) ? i : {}), (s = !1), (r[e] = k.extend(c, o, n)))
                                        : void 0 !== n && (r[e] = n));
                return r;
            }),
            k.extend({
                expando: "jQuery" + (u + Math.random()).replace(/\D/g, ""),
                isReady: !0,
                error: function (t) {
                    throw new Error(t);
                },
                noop: function () {},
                isPlainObject: function (t) {
                    var e, i;
                    return !(!t || "[object Object]" !== o.call(t)) && (!(e = n(t)) || ("function" === typeof (i = v.call(e, "constructor") && e.constructor) && r.call(i) === c));
                },
                isEmptyObject: function (t) {
                    var e;
                    for (e in t) return !1;
                    return !0;
                },
                globalEval: function (t, e) {
                    w(t, { nonce: e && e.nonce });
                },
                each: function (t, e) {
                    var i,
                        n = 0;
                    if (p(t)) for (i = t.length; n < i && !1 !== e.call(t[n], n, t[n]); n++);
                    else for (n in t) if (!1 === e.call(t[n], n, t[n])) break;
                    return t;
                },
                trim: function (t) {
                    return null === t ? "" : (t + "").replace(d, "");
                },
                makeArray: function (t, e) {
                    var i = e || [];
                    return null !==    t && (p(Object(t)) ? k.merge(i, "string" === typeof t ? [t] : t) : l.call(i, t)), i;
                },
                inArray: function (t, e, i) {
                    return null === e ? -1 : s.call(e, t, i);
                },
                merge: function (t, e) {
                    for (var i = +e.length, n = 0, s = t.length; n < i; n++) t[s++] = e[n];
                    return (t.length = s), t;
                },
                grep: function (t, e, i) {
                    for (var n = [], s = 0, o = t.length, r = !i; s < o; s++) !e(t[s], s) !==    r && n.push(t[s]);
                    return n;
                },
                map: function (t, e, i) {
                    var n,
                        s,
                        o = 0,
                        r = [];
                    if (p(t)) for (n = t.length; o < n; o++) null !==    (s = e(t[o], o, i)) && r.push(s);
                    else for (o in t) null !==    (s = e(t[o], o, i)) && r.push(s);
                    return m.apply([], r);
                },
                guid: 1,
                support: y
            });
            var Symbol;
            "function" === typeof Symbol && (k.fn[Symbol.iterator] = e[Symbol.iterator]),
            k.each("Boolean Number String Function Array Date RegExp Object Error Symbol".split(" "), function (t, e) {
                i["[object " + e + "]"] = e.toLowerCase();
            });
        var f = (function (i) {
            function u(t, e, i) {
                var n = "0x" + e - 65536;
                return n !==    n || i ? e : n < 0 ? String.fromCharCode(65536 + n) : String.fromCharCode((n >> 10) | 55296, (1023 & n) | 56320);
            }
            function s() {
                x();
            }
            var t,
                p,
                w,
                o,
                r,
                f,
                d,
                g,
                _,
                l,
                c,
                x,
                C,
                a,
                D,
                m,
                h,
                v,
                y,
                k = "sizzle" + +new Date(),
                b = i.document,
                T = 0,
                n = 0,
                S = lt(),
                E = lt(),
                A = lt(),
                P = lt(),
                $ = function (t, e) {
                    return t === e && (c = !0), 0;
                },
                I = {}.hasOwnProperty,
                e = [],
                z = e.pop,
                O = e.push,
                N = e.push,
                M = e.slice,
                F = function (t, e) {
                    for (var i = 0, n = t.length; i < n; i++) if (t[i] === e) return i;
                    return -1;
                },
                L = "checked|selected|async|autofocus|autoplay|controls|defer|disabled|hidden|ismap|loop|multiple|open|readonly|required|scoped",
                H = "[\\x20\\t\\r\\n\\f]",
                R = "(?:\\\\.|[\\w-]|[^\0-\\xa0])+",
                j = "\\[" + H + "*(" + R + ")(?:" + H + "*([*^$|!~]?=)" + H + "*(?:'((?:\\\\.|[^\\\\'])*)'|\"((?:\\\\.|[^\\\\\"])*)\"|(" + R + "))|)" + H + "*\\]",
                U = ":(" + R + ")(?:\\((('((?:\\\\.|[^\\\\'])*)'|\"((?:\\\\.|[^\\\\\"])*)\")|((?:\\\\.|[^\\\\()[\\]]|" + j + ")*)|.*)\\)|)",
                W = new RegExp(H + "+", "g"),
                q = new RegExp("^" + H + "+|((?:^|[^\\\\])(?:\\\\.)*)" + H + "+$", "g"),
                B = new RegExp("^" + H + "*," + H + "*"),
                Y = new RegExp("^" + H + "*([>+~]|" + H + ")" + H + "*"),
                V = new RegExp(H + "|>"),
                X = new RegExp(U),
                G = new RegExp("^" + R + "$"),
                Q = {
                    ID: new RegExp("^#(" + R + ")"),
                    CLASS: new RegExp("^\\.(" + R + ")"),
                    TAG: new RegExp("^(" + R + "|[*])"),
                    ATTR: new RegExp("^" + j),
                    PSEUDO: new RegExp("^" + U),
                    CHILD: new RegExp("^:(only|first|last|nth|nth-last)-(child|of-type)(?:\\(" + H + "*(even|odd|(([+-]|)(\\d*)n|)" + H + "*(?:([+-]|)" + H + "*(\\d+)|))" + H + "*\\)|)", "i"),
                    bool: new RegExp("^(?:" + L + ")$", "i"),
                    needsContext: new RegExp("^" + H + "*[>+~]|:(even|odd|eq|gt|lt|nth|first|last)(?:\\(" + H + "*((?:-\\d)?\\d*)" + H + "*\\)|)(?=[^-]|$)", "i")
                },
                K = /HTML$/i,
                Z = /^(?:input|select|textarea|button)$/i,
                J = /^h\d$/i,
                tt = /^[^{]+\{\s*\[native \w/,
                et = /^(?:#([\w-]+)|(\w+)|\.([\w-]+))$/,
                it = /[+~]/,
                nt = new RegExp("\\\\([\\da-f]{1,6}" + H + "?|(" + H + ")|.)", "ig"),
                st = /([\0-\x1f\x7f]|^-?\d)|^-$|[^\0-\x1f\x7f-\uFFFF\w-]/g,
                ot = function (t, e) {
                    return e ? ("\0" === t ? "�" : t.slice(0, -1) + "\\" + t.charCodeAt(t.length - 1).toString(16) + " ") : "\\" + t;
                },
                rt = wt(
                    function (t) {
                        return !0 === t.disabled && "fieldset" === t.nodeName.toLowerCase();
                    },
                    { dir: "parentNode", next: "legend" }
                );
            try {
                N.apply((e = M.call(b.childNodes)), b.childNodes), e[b.childNodes.length].nodeType;
            } catch (t) {
                N = {
                    apply: e.length
                        ? function (t, e) {
                              O.apply(t, M.call(e));
                          }
                        : function (t, e) {
                              for (var i = t.length, n = 0; (t[i++] = e[n++]); );
                              t.length = i - 1;
                          }
                };
            }
            function at(e, t, i, n) {
                var s,
                    o,
                    r,
                    a,
                    l,
                    c,
                    h,
                    u = t && t.ownerDocument,
                    d = t ? t.nodeType : 9;
                if (((i = i || []), "string" !== typeof e || !e || (1 !== d && 9 !== d && 11 !== d))) return i;
                if (!n && ((t ? t.ownerDocument || t : b) !== C && x(t), (t = t || C), D)) {
                    if (11 !== d && (l = et.exec(e)))
                        if ((s = l[1])) {
                            if (9 === d) {
                                if (!(r = t.getElementById(s))) return i;
                                if (r.id === s) return i.push(r), i;
                            } else if (u && (r = u.getElementById(s)) && y(t, r) && r.id === s) return i.push(r), i;
                        } else {
                            if (l[2]) return N.apply(i, t.getElementsByTagName(e)), i;
                            if ((s = l[3]) && p.getElementsByClassName && t.getElementsByClassName) return N.apply(i, t.getElementsByClassName(s)), i;
                        }
                    if (p.qsa && !P[e + " "] && (!m || !m.test(e)) && (1 !== d || "object" !== t.nodeName.toLowerCase())) {
                        if (((h = e), (u = t), 1 === d && V.test(e))) {
                            for ((a = t.getAttribute("id")) ? (a = a.replace(st, ot)) : t.setAttribute("id", (a = k)), o = (c = f(e)).length; o--; ) c[o] = "#" + a + " " + bt(c[o]);
                            (h = c.join(",")), (u = (it.test(e) && vt(t.parentNode)) || t);
                        }
                        try {
                            return N.apply(i, u.querySelectorAll(h)), i;
                        } catch (t) {
                            P(e, !0);
                        } finally {
                            a === k && t.removeAttribute("id");
                        }
                    }
                }
                return g(e.replace(q, "$1"), t, i, n);
            }
            function lt() {
                var n = [];
                return function t(e, i) {
                    return n.push(e + " ") > w.cacheLength && delete t[n.shift()], (t[e + " "] = i);
                };
            }
            function ct(t) {
                return (t[k] = !0), t;
            }
            function ht(t) {
                var e = C.createElement("fieldset");
                try {
                    return !!t(e);
                } catch (t) {
                    return !1;
                } finally {
                    e.parentNode && e.parentNode.removeChild(e), (e = null);
                }
            }
            function ut(t, e) {
                for (var i = t.split("|"), n = i.length; n--; ) w.attrHandle[i[n]] = e;
            }
            function dt(t, e) {
                var i = e && t,
                    n = i && 1 === t.nodeType && 1 === e.nodeType && t.sourceIndex - e.sourceIndex;
                if (n) return n;
                if (i) for (; (i = i.nextSibling); ) if (i === e) return -1;
                return t ? 1 : -1;
            }
            function pt(e) {
                return function (t) {
                    return "input" === t.nodeName.toLowerCase() && t.type === e;
                };
            }
            function ft(i) {
                return function (t) {
                    var e = t.nodeName.toLowerCase();
                    return ("input" === e || "button" === e) && t.type === i;
                };
            }
            function gt(e) {
                return function (t) {
                    return "form" in t
                        ? t.parentNode && !1 === t.disabled
                            ? "label" in t
                                ? "label" in t.parentNode
                                    ? t.parentNode.disabled === e
                                    : t.disabled === e
                                : t.isDisabled === e || (t.isDisabled !== !e && rt(t) === e)
                            : t.disabled === e
                        : "label" in t && t.disabled === e;
                };
            }
            function mt(r) {
                return ct(function (o) {
                    return (
                        (o = +o),
                        ct(function (t, e) {
                            for (var i, n = r([], t.length, o), s = n.length; s--; ) t[(i = n[s])] && (t[i] = !(e[i] = t[i]));
                        })
                    );
                });
            }
            function vt(t) {
                return t && void 0 !== t.getElementsByTagName && t;
            }
            for (t in ((p = at.support = {}),
            (r = at.isXML = function (t) {
                var e = t.namespaceURI,
                    i = (t.ownerDocument || t).documentElement;
                return !K.test(e || (i && i.nodeName) || "HTML");
            }),
            (x = at.setDocument = function (t) {
                var e,
                    i,
                    n = t ? t.ownerDocument || t : b;
                return (
                    n !== C &&
                        9 === n.nodeType &&
                        n.documentElement &&
                        ((a = (C = n).documentElement),
                        (D = !r(C)),
                        b !== C && (i = C.defaultView) && i.top !== i && (i.addEventListener ? i.addEventListener("unload", s, !1) : i.attachEvent && i.attachEvent("onunload", s)),
                        (p.attributes = ht(function (t) {
                            return (t.className = "i"), !t.getAttribute("className");
                        })),
                        (p.getElementsByTagName = ht(function (t) {
                            return t.appendChild(C.createComment("")), !t.getElementsByTagName("*").length;
                        })),
                        (p.getElementsByClassName = tt.test(C.getElementsByClassName)),
                        (p.getById = ht(function (t) {
                            return (a.appendChild(t).id = k), !C.getElementsByName || !C.getElementsByName(k).length;
                        })),
                        p.getById
                            ? ((w.filter.ID = function (t) {
                                  var e = t.replace(nt, u);
                                  return function (t) {
                                      return t.getAttribute("id") === e;
                                  };
                              }),
                              (w.find.ID = function (t, e) {
                                  if (void 0 !== e.getElementById && D) {
                                      var i = e.getElementById(t);
                                      return i ? [i] : [];
                                  }
                              }))
                            : ((w.filter.ID = function (t) {
                                  var i = t.replace(nt, u);
                                  return function (t) {
                                      var e = void 0 !== t.getAttributeNode && t.getAttributeNode("id");
                                      return e && e.value === i;
                                  };
                              }),
                              (w.find.ID = function (t, e) {
                                  if (void 0 !== e.getElementById && D) {
                                      var i,
                                          n,
                                          s,
                                          o = e.getElementById(t);
                                      if (o) {
                                          if ((i = o.getAttributeNode("id")) && i.value === t) return [o];
                                          for (s = e.getElementsByName(t), n = 0; (o = s[n++]); ) if ((i = o.getAttributeNode("id")) && i.value === t) return [o];
                                      }
                                      return [];
                                  }
                              })),
                        (w.find.TAG = p.getElementsByTagName
                            ? function (t, e) {
                                  return void 0 !== e.getElementsByTagName ? e.getElementsByTagName(t) : p.qsa ? e.querySelectorAll(t) : void 0;
                              }
                            : function (t, e) {
                                  var i,
                                      n = [],
                                      s = 0,
                                      o = e.getElementsByTagName(t);
                                  if ("*" !== t) return o;
                                  for (; (i = o[s++]); ) 1 === i.nodeType && n.push(i);
                                  return n;
                              }),
                        (w.find.CLASS =
                            p.getElementsByClassName &&
                            function (t, e) {
                                if (void 0 !== e.getElementsByClassName && D) return e.getElementsByClassName(t);
                            }),
                        (h = []),
                        (m = []),
                        (p.qsa = tt.test(C.querySelectorAll)) &&
                            (ht(function (t) {
                                (a.appendChild(t).innerHTML = "<a id='" + k + "'></a><select id='" + k + "-\r\\' msallowcapture=''><option selected=''></option></select>"),
                                    t.querySelectorAll("[msallowcapture^='']").length && m.push("[*^$]=" + H + "*(?:''|\"\")"),
                                    t.querySelectorAll("[selected]").length || m.push("\\[" + H + "*(?:value|" + L + ")"),
                                    t.querySelectorAll("[id~=" + k + "-]").length || m.push("~="),
                                    t.querySelectorAll(":checked").length || m.push(":checked"),
                                    t.querySelectorAll("a#" + k + "+*").length || m.push(".#.+[+~]");
                            }),
                            ht(function (t) {
                                t.innerHTML = "<a href='' disabled='disabled'></a><select disabled='disabled'><option/></select>";
                                var e = C.createElement("input");
                                e.setAttribute("type", "hidden"),
                                    t.appendChild(e).setAttribute("name", "D"),
                                    t.querySelectorAll("[name=d]").length && m.push("name" + H + "*[*^$|!~]?="),
                                    2 !== t.querySelectorAll(":enabled").length && m.push(":enabled", ":disabled"),
                                    (a.appendChild(t).disabled = !0),
                                    2 !== t.querySelectorAll(":disabled").length && m.push(":enabled", ":disabled"),
                                    t.querySelectorAll("*,:x"),
                                    m.push(",.*:");
                            })),
                        (p.matchesSelector = tt.test((v = a.matches || a.webkitMatchesSelector || a.mozMatchesSelector || a.oMatchesSelector || a.msMatchesSelector))) &&
                            ht(function (t) {
                                (p.disconnectedMatch = v.call(t, "*")), v.call(t, "[s!='']:x"), h.push("!=", U);
                            }),
                        (m = m.length && new RegExp(m.join("|"))),
                        (h = h.length && new RegExp(h.join("|"))),
                        (e = tt.test(a.compareDocumentPosition)),
                        (y =
                            e || tt.test(a.contains)
                                ? function (t, e) {
                                      var i = 9 === t.nodeType ? t.documentElement : t,
                                          n = e && e.parentNode;
                                      return t === n || !(!n || 1 !== n.nodeType || !(i.contains ? i.contains(n) : t.compareDocumentPosition && 16 & t.compareDocumentPosition(n)));
                                  }
                                : function (t, e) {
                                      if (e) for (; (e = e.parentNode); ) if (e === t) return !0;
                                      return !1;
                                  }),
                        ($ = e
                            ? function (t, e) {
                                  if (t === e) return (c = !0), 0;
                                  var i = !t.compareDocumentPosition - !e.compareDocumentPosition;
                                  return (
                                      i ||
                                      (1 & (i = (t.ownerDocument || t) === (e.ownerDocument || e) ? t.compareDocumentPosition(e) : 1) || (!p.sortDetached && e.compareDocumentPosition(t) === i)
                                          ? t === C || (t.ownerDocument === b && y(b, t))
                                              ? -1
                                              : e === C || (e.ownerDocument === b && y(b, e))
                                              ? 1
                                              : l
                                              ? F(l, t) - F(l, e)
                                              : 0
                                          : 4 & i
                                          ? -1
                                          : 1)
                                  );
                              }
                            : function (t, e) {
                                  if (t === e) return (c = !0), 0;
                                  var i,
                                      n = 0,
                                      s = t.parentNode,
                                      o = e.parentNode,
                                      r = [t],
                                      a = [e];
                                  if (!s || !o) return t === C ? -1 : e === C ? 1 : s ? -1 : o ? 1 : l ? F(l, t) - F(l, e) : 0;
                                  if (s === o) return dt(t, e);
                                  for (i = t; (i = i.parentNode); ) r.unshift(i);
                                  for (i = e; (i = i.parentNode); ) a.unshift(i);
                                  for (; r[n] === a[n]; ) n++;
                                  return n ? dt(r[n], a[n]) : r[n] === b ? -1 : a[n] === b ? 1 : 0;
                              })),
                    C
                );
            }),
            (at.matches = function (t, e) {
                return at(t, null, null, e);
            }),
            (at.matchesSelector = function (t, e) {
                if (((t.ownerDocument || t) !== C && x(t), p.matchesSelector && D && !P[e + " "] && (!h || !h.test(e)) && (!m || !m.test(e))))
                    try {
                        var i = v.call(t, e);
                        if (i || p.disconnectedMatch || (t.document && 11 !== t.document.nodeType)) return i;
                    } catch (t) {
                        P(e, !0);
                    }
                return 0 < at(e, C, null, [t]).length;
            }),
            (at.contains = function (t, e) {
                return (t.ownerDocument || t) !== C && x(t), y(t, e);
            }),
            (at.attr = function (t, e) {
                (t.ownerDocument || t) !== C && x(t);
                var i = w.attrHandle[e.toLowerCase()],
                    n = i && I.call(w.attrHandle, e.toLowerCase()) ? i(t, e, !D) : void 0;
                return void 0 !== n ? n : p.attributes || !D ? t.getAttribute(e) : (n = t.getAttributeNode(e)) && n.specified ? n.value : null;
            }),
            (at.escape = function (t) {
                return (t + "").replace(st, ot);
            }),
            (at.error = function (t) {
                throw new Error("Syntax error, unrecognized expression: " + t);
            }),
            (at.uniqueSort = function (t) {
                var e,
                    i = [],
                    n = 0,
                    s = 0;
                if (((c = !p.detectDuplicates), (l = !p.sortStable && t.slice(0)), t.sort($), c)) {
                    for (; (e = t[s++]); ) e === t[s] && (n = i.push(s));
                    for (; n--; ) t.splice(i[n], 1);
                }
                return (l = null), t;
            }),
            (o = at.getText = function (t) {
                var e,
                    i = "",
                    n = 0,
                    s = t.nodeType;
                if (s) {
                    if (1 === s || 9 === s || 11 === s) {
                        if ("string" === typeof t.textContent) return t.textContent;
                        for (t = t.firstChild; t; t = t.nextSibling) i += o(t);
                    } else if (3 === s || 4 === s) return t.nodeValue;
                } else for (; (e = t[n++]); ) i += o(e);
                return i;
            }),
            ((w = at.selectors = {
                cacheLength: 50,
                createPseudo: ct,
                match: Q,
                attrHandle: {},
                find: {},
                relative: { ">": { dir: "parentNode", first: !0 }, " ": { dir: "parentNode" }, "+": { dir: "previousSibling", first: !0 }, "~": { dir: "previousSibling" } },
                preFilter: {
                    ATTR: function (t) {
                        return (t[1] = t[1].replace(nt, u)), (t[3] = (t[3] || t[4] || t[5] || "").replace(nt, u)), "~=" === t[2] && (t[3] = " " + t[3] + " "), t.slice(0, 4);
                    },
                    CHILD: function (t) {
                        return (
                            (t[1] = t[1].toLowerCase()),
                            "nth" === t[1].slice(0, 3) ? (t[3] || at.error(t[0]), (t[4] = +(t[4] ? t[5] + (t[6] || 1) : 2 * ("even" === t[3] || "odd" === t[3]))), (t[5] = +(t[7] + t[8] || "odd" === t[3]))) : t[3] && at.error(t[0]),
                            t
                        );
                    },
                    PSEUDO: function (t) {
                        var e,
                            i = !t[6] && t[2];
                        return Q.CHILD.test(t[0])
                            ? null
                            : (t[3] ? (t[2] = t[4] || t[5] || "") : i && X.test(i) && (e = f(i, !0)) && (e = i.indexOf(")", i.length - e) - i.length) && ((t[0] = t[0].slice(0, e)), (t[2] = i.slice(0, e))), t.slice(0, 3));
                    },
                },
                filter: {
                    TAG: function (t) {
                        var e = t.replace(nt, u).toLowerCase();
                        return "*" === t
                            ? function () {
                                  return !0;
                              }
                            : function (t) {
                                  return t.nodeName && t.nodeName.toLowerCase() === e;
                              };
                    },
                    CLASS: function (t) {
                        var e = S[t + " "];
                        return (
                            e ||
                            ((e = new RegExp("(^|" + H + ")" + t + "(" + H + "|$)")) &&
                                S(t, function (t) {
                                    return e.test(("string" === typeof t.className && t.className) || (void 0 !== t.getAttribute && t.getAttribute("class")) || "");
                                }))
                        );
                    },
                    ATTR: function (i, n, s) {
                        return function (t) {
                            var e = at.attr(t, i);
                            return null === e
                                ? "!=" === n
                                : !n ||
                                      ((e += ""),
                                      "=" === n
                                          ? e === s
                                          : "!=" === n
                                          ? e !== s
                                          : "^=" === n
                                          ? s && 0 === e.indexOf(s)
                                          : "*=" === n
                                          ? s && -1 < e.indexOf(s)
                                          : "$=" === n
                                          ? s && e.slice(-s.length) === s
                                          : "~=" === n
                                          ? -1 < (" " + e.replace(W, " ") + " ").indexOf(s)
                                          : "|=" === n && (e === s || e.slice(0, s.length + 1) === s + "-"));
                        };
                    },
                    CHILD: function (f, t, e, g, m) {
                        var v = "nth" !== f.slice(0, 3),
                            y = "last" !== f.slice(-4),
                            b = "of-type" === t;
                        return 1 === g && 0 === m
                            ? function (t) {
                                  return !!t.parentNode;
                              }
                            : function (t, e, i) {
                                  var n,
                                      s,
                                      o,
                                      r,
                                      a,
                                      l,
                                      c = v !==    y ? "nextSibling" : "previousSibling",
                                      h = t.parentNode,
                                      u = b && t.nodeName.toLowerCase(),
                                      d = !i && !b,
                                      p = !1;
                                  if (h) {
                                      if (v) {
                                          for (; c; ) {
                                              for (r = t; (r = r[c]); ) if (b ? r.nodeName.toLowerCase() === u : 1 === r.nodeType) return !1;
                                              l = c = "only" === f && !l && "nextSibling";
                                          }
                                          return !0;
                                      }
                                      if (((l = [y ? h.firstChild : h.lastChild]), y && d)) {
                                          for (
                                              p = (a = (n = (s = (o = (r = h)[k] || (r[k] = {}))[r.uniqueID] || (o[r.uniqueID] = {}))[f] || [])[0] === T && n[1]) && n[2], r = a && h.childNodes[a];
                                              (r = (++a && r && r[c]) || (p = a = 0) || l.pop());

                                          )
                                              if (1 === r.nodeType && ++p && r === t) {
                                                  s[f] = [T, a, p];
                                                  break;
                                              }
                                      } else if ((d && (p = a = (n = (s = (o = (r = t)[k] || (r[k] = {}))[r.uniqueID] || (o[r.uniqueID] = {}))[f] || [])[0] === T && n[1]), !1 === p))
                                          for (
                                              ;
                                              (r = (++a && r && r[c]) || (p = a = 0) || l.pop()) &&
                                              ((b ? r.nodeName.toLowerCase() !== u : 1 !== r.nodeType) || !++p || (d && ((s = (o = r[k] || (r[k] = {}))[r.uniqueID] || (o[r.uniqueID] = {}))[f] = [T, p]), r !== t));

                                          );
                                      return (p -= m) === g || (p % g === 0 && 0 <= p / g);
                                  }
                              };
                    },
                    PSEUDO: function (t, o) {
                        var e,
                            r = w.pseudos[t] || w.setFilters[t.toLowerCase()] || at.error("unsupported pseudo: " + t);
                        return r[k]
                            ? r(o)
                            : 1 < r.length
                            ? ((e = [t, t, "", o]),
                              w.setFilters.hasOwnProperty(t.toLowerCase())
                                  ? ct(function (t, e) {
                                        for (var i, n = r(t, o), s = n.length; s--; ) t[(i = F(t, n[s]))] = !(e[i] = n[s]);
                                    })
                                  : function (t) {
                                        return r(t, 0, e);
                                    })
                            : r;
                    },
                },
                pseudos: {
                    not: ct(function (t) {
                        var n = [],
                            s = [],
                            a = d(t.replace(q, "$1"));
                        return a[k]
                            ? ct(function (t, e, i, n) {
                                  for (var s, o = a(t, null, n, []), r = t.length; r--; ) (s = o[r]) && (t[r] = !(e[r] = s));
                              })
                            : function (t, e, i) {
                                  return (n[0] = t), a(n, null, i, s), (n[0] = null), !s.pop();
                              };
                    }),
                    has: ct(function (e) {
                        return function (t) {
                            return 0 < at(e, t).length;
                        };
                    }),
                    contains: ct(function (e) {
                        return (
                            (e = e.replace(nt, u)),
                            function (t) {
                                return -1 < (t.textContent || o(t)).indexOf(e);
                            }
                        );
                    }),
                    lang: ct(function (i) {
                        return (
                            G.test(i || "") || at.error("unsupported lang: " + i),
                            (i = i.replace(nt, u).toLowerCase()),
                            function (t) {
                                var e;
                                do {
                                    if ((e = D ? t.lang : t.getAttribute("xml:lang") || t.getAttribute("lang"))) return (e = e.toLowerCase()) === i || 0 === e.indexOf(i + "-");
                                } while ((t = t.parentNode) && 1 === t.nodeType);
                                return !1;
                            }
                        );
                    }),
                    target: function (t) {
                        var e = i.location && i.location.hash;
                        return e && e.slice(1) === t.id;
                    },
                    root: function (t) {
                        return t === a;
                    },
                    focus: function (t) {
                        return t === C.activeElement && (!C.hasFocus || C.hasFocus()) && !!(t.type || t.href || ~t.tabIndex);
                    },
                    enabled: gt(!1),
                    disabled: gt(!0),
                    checked: function (t) {
                        var e = t.nodeName.toLowerCase();
                        return ("input" === e && !!t.checked) || ("option" === e && !!t.selected);
                    },
                    selected: function (t) {
                        return t.parentNode && t.parentNode.selectedIndex, !0 === t.selected;
                    },
                    empty: function (t) {
                        for (t = t.firstChild; t; t = t.nextSibling) if (t.nodeType < 6) return !1;
                        return !0;
                    },
                    parent: function (t) {
                        return !w.pseudos.empty(t);
                    },
                    header: function (t) {
                        return J.test(t.nodeName);
                    },
                    input: function (t) {
                        return Z.test(t.nodeName);
                    },
                    button: function (t) {
                        var e = t.nodeName.toLowerCase();
                        return ("input" === e && "button" === t.type) || "button" === e;
                    },
                    text: function (t) {
                        var e;
                        return "input" === t.nodeName.toLowerCase() && "text" === t.type && (null === (e = t.getAttribute("type")) || "text" === e.toLowerCase());
                    },
                    first: mt(function () {
                        return [0];
                    }),
                    last: mt(function (t, e) {
                        return [e - 1];
                    }),
                    eq: mt(function (t, e, i) {
                        return [i < 0 ? i + e : i];
                    }),
                    even: mt(function (t, e) {
                        for (var i = 0; i < e; i += 2) t.push(i);
                        return t;
                    }),
                    odd: mt(function (t, e) {
                        for (var i = 1; i < e; i += 2) t.push(i);
                        return t;
                    }),
                    lt: mt(function (t, e, i) {
                        for (var n = i < 0 ? i + e : e < i ? e : i; 0 <= --n; ) t.push(n);
                        return t;
                    }),
                    gt: mt(function (t, e, i) {
                        for (var n = i < 0 ? i + e : i; ++n < e; ) t.push(n);
                        return t;
                    }),
                },
            }).pseudos.nth = w.pseudos.eq),
            { radio: !0, checkbox: !0, file: !0, password: !0, image: !0 }))
                w.pseudos[t] = pt(t);
            for (t in { submit: !0, reset: !0 }) w.pseudos[t] = ft(t);
            function yt() {}
            function bt(t) {
                for (var e = 0, i = t.length, n = ""; e < i; e++) n += t[e].value;
                return n;
            }
            function wt(a, t, e) {
                var l = t.dir,
                    c = t.next,
                    h = c || l,
                    u = e && "parentNode" === h,
                    d = n++;
                return t.first
                    ? function (t, e, i) {
                          for (; (t = t[l]); ) if (1 === t.nodeType || u) return a(t, e, i);
                          return !1;
                      }
                    : function (t, e, i) {
                          var n,
                              s,
                              o,
                              r = [T, d];
                          if (i) {
                              for (; (t = t[l]); ) if ((1 === t.nodeType || u) && a(t, e, i)) return !0;
                          } else
                              for (; (t = t[l]); )
                                  if (1 === t.nodeType || u)
                                      if (((s = (o = t[k] || (t[k] = {}))[t.uniqueID] || (o[t.uniqueID] = {})), c && c === t.nodeName.toLowerCase())) t = t[l] || t;
                                      else {
                                          if ((n = s[h]) && n[0] === T && n[1] === d) return (r[2] = n[2]);
                                          if (((s[h] = r)[2] = a(t, e, i))) return !0;
                                      }
                          return !1;
                      };
            }
            function _t(s) {
                return 1 < s.length
                    ? function (t, e, i) {
                          for (var n = s.length; n--; ) if (!s[n](t, e, i)) return !1;
                          return !0;
                      }
                    : s[0];
            }
            function xt(t, e, i, n, s) {
                for (var o, r = [], a = 0, l = t.length, c = null !== e; a < l; a++) (o = t[a]) && ((i && !i(o, n, s)) || (r.push(o), c && e.push(a)));
                return r;
            }
            function Ct(p, f, g, m, v, t) {
                return (
                    m && !m[k] && (m = Ct(m)),
                    v && !v[k] && (v = Ct(v, t)),
                    ct(function (t, e, i, n) {
                        var s,
                            o,
                            r,
                            a = [],
                            l = [],
                            c = e.length,
                            h =
                                t ||
                                (function (t, e, i) {
                                    for (var n = 0, s = e.length; n < s; n++) at(t, e[n], i);
                                    return i;
                                })(f || "*", i.nodeType ? [i] : i, []),
                            u = !p || (!t && f) ? h : xt(h, a, p, i, n),
                            d = g ? (v || (t ? p : c || m) ? [] : e) : u;
                        if ((g && g(u, d, i, n), m)) for (s = xt(d, l), m(s, [], i, n), o = s.length; o--; ) (r = s[o]) && (d[l[o]] = !(u[l[o]] = r));
                        if (t) {
                            if (v || p) {
                                if (v) {
                                    for (s = [], o = d.length; o--; ) (r = d[o]) && s.push((u[o] = r));
                                    v(null, (d = []), s, n);
                                }
                                for (o = d.length; o--; ) (r = d[o]) && -1 < (s = v ? F(t, r) : a[o]) && (t[s] = !(e[s] = r));
                            }
                        } else (d = xt(d === e ? d.splice(c, d.length) : d)), v ? v(null, e, d, n) : N.apply(e, d);
                    })
                );
            }
            function Dt(t) {
                for (
                    var s,
                        e,
                        i,
                        n = t.length,
                        o = w.relative[t[0].type],
                        r = o || w.relative[" "],
                        a = o ? 1 : 0,
                        l = wt(
                            function (t) {
                                return t === s;
                            },
                            r,
                            !0
                        ),
                        c = wt(
                            function (t) {
                                return -1 < F(s, t);
                            },
                            r,
                            !0
                        ),
                        h = [
                            function (t, e, i) {
                                var n = (!o && (i || e !== _)) || ((s = e).nodeType ? l : c)(t, e, i);
                                return (s = null), n;
                            },
                        ];
                    a < n;
                    a++
                )
                    if ((e = w.relative[t[a].type])) h = [wt(_t(h), e)];
                    else {
                        if ((e = w.filter[t[a].type].apply(null, t[a].matches))[k]) {
                            for (i = ++a; i < n && !w.relative[t[i].type]; i++);
                            return Ct(1 < a && _t(h), 1 < a && bt(t.slice(0, a - 1).concat({ value: " " === t[a - 2].type ? "*" : "" })).replace(q, "$1"), e, a < i && Dt(t.slice(a, i)), i < n && Dt((t = t.slice(i))), i < n && bt(t));
                        }
                        h.push(e);
                    }
                return _t(h);
            }
            function kt(m, v) {
                function t(t, e, i, n, s) {
                    var o,
                        r,
                        a,
                        l = 0,
                        c = "0",
                        h = t && [],
                        u = [],
                        d = _,
                        p = t || (b && w.find.TAG("*", s)),
                        f = (T += null === d ? 1 : Math.random() || 0.1),
                        g = p.length;
                    for (s && (_ = e === C || e || s); c !== g && null !== (o = p[c]); c++) {
                        if (b && o) {
                            for (r = 0, e || o.ownerDocument === C || (x(o), (i = !D)); (a = m[r++]); )
                                if (a(o, e || C, i)) {
                                    n.push(o);
                                    break;
                                }
                            s && (T = f);
                        }
                        y && ((o = !a && o) && l--, t && h.push(o));
                    }
                    if (((l += c), y && c !== l)) {
                        for (r = 0; (a = v[r++]); ) a(h, u, e, i);
                        if (t) {
                            if (0 < l) for (; c--; ) h[c] || u[c] || (u[c] = z.call(n));
                            u = xt(u);
                        }
                        N.apply(n, u), s && !t && 0 < u.length && 1 < l + v.length && at.uniqueSort(n);
                    }
                    return s && ((T = f), (_ = d)), h;
                }
                var y = 0 < v.length,
                    b = 0 < m.length;
                return y ? ct(t) : t;
            }
            return (
                (yt.prototype = w.filters = w.pseudos),
                (w.setFilters = new yt()),
                (f = at.tokenize = function (t, e) {
                    var i,
                        n,
                        s,
                        o,
                        r,
                        a,
                        l,
                        c = E[t + " "];
                    if (c) return e ? 0 : c.slice(0);
                    for (r = t, a = [], l = w.preFilter; r; ) {
                        for (o in ((i && !(n = B.exec(r))) || (n && (r = r.slice(n[0].length) || r), a.push((s = []))),
                        (i = !1),
                        (n = Y.exec(r)) && ((i = n.shift()), s.push({ value: i, type: n[0].replace(q, " ") }), (r = r.slice(i.length))),
                        w.filter))
                            !(n = Q[o].exec(r)) || (l[o] && !(n = l[o](n))) || ((i = n.shift()), s.push({ value: i, type: o, matches: n }), (r = r.slice(i.length)));
                        if (!i) break;
                    }
                    return e ? r.length : r ? at.error(t) : E(t, a).slice(0);
                }),
                (d = at.compile = function (t, e) {
                    var i,
                        n = [],
                        s = [],
                        o = A[t + " "];
                    if (!o) {
                        for (i = (e = e || f(t)).length; i--; ) (o = Dt(e[i]))[k] ? n.push(o) : s.push(o);
                        (o = A(t, kt(s, n))).selector = t;
                    }
                    return o;
                }),
                (g = at.select = function (t, e, i, n) {
                    var s,
                        o,
                        r,
                        a,
                        l,
                        c = "function" === typeof t && t,
                        h = !n && f((t = c.selector || t));
                    if (((i = i || []), 1 === h.length)) {
                        if (2 < (o = h[0] = h[0].slice(0)).length && "ID" === (r = o[0]).type && 9 === e.nodeType && D && w.relative[o[1].type]) {
                            if (!(e = (w.find.ID(r.matches[0].replace(nt, u), e) || [])[0])) return i;
                            c && (e = e.parentNode), (t = t.slice(o.shift().value.length));
                        }
                        for (s = Q.needsContext.test(t) ? 0 : o.length; s-- && ((r = o[s]), !w.relative[(a = r.type)]); )
                            if ((l = w.find[a]) && (n = l(r.matches[0].replace(nt, u), (it.test(o[0].type) && vt(e.parentNode)) || e))) {
                                if ((o.splice(s, 1), !(t = n.length && bt(o)))) return N.apply(i, n), i;
                                break;
                            }
                    }
                    return (c || d(t, h))(n, e, !D, i, !e || (it.test(t) && vt(e.parentNode)) || e), i;
                }),
                (p.sortStable = k.split("").sort($).join("") === k),
                (p.detectDuplicates = !!c),
                x(),
                (p.sortDetached = ht(function (t) {
                    return 1 & t.compareDocumentPosition(C.createElement("fieldset"));
                })),
                ht(function (t) {
                    return (t.innerHTML = "<a href='#'></a>"), "#" === t.firstChild.getAttribute("href");
                }) ||
                    ut("type|href|height|width", function (t, e, i) {
                        if (!i) return t.getAttribute(e, "type" === e.toLowerCase() ? 1 : 2);
                    }),
                (p.attributes &&
                    ht(function (t) {
                        return (t.innerHTML = "<input/>"), t.firstChild.setAttribute("value", ""), "" === t.firstChild.getAttribute("value");
                    })) ||
                    ut("value", function (t, e, i) {
                        if (!i && "input" === t.nodeName.toLowerCase()) return t.defaultValue;
                    }),
                ht(function (t) {
                    return null === t.getAttribute("disabled");
                }) ||
                    ut(L, function (t, e, i) {
                        var n;
                        if (!i) return !0 === t[e] ? e.toLowerCase() : (n = t.getAttributeNode(e)) && n.specified ? n.value : null;
                    }),
                at
            );
        })(C);
        (k.find = f), (k.expr = f.selectors), (k.expr[":"] = k.expr.pseudos), (k.uniqueSort = k.unique = f.uniqueSort), (k.text = f.getText), (k.isXMLDoc = f.isXML), (k.contains = f.contains), (k.escapeSelector = f.escape);
        function x(t, e, i) {
            for (var n = [], s = void 0 !== i; (t = t[e]) && 9 !== t.nodeType; )
                if (1 === t.nodeType) {
                    if (s && k(t).is(i)) break;
                    n.push(t);
                }
            return n;
        }
        function T(t, e) {
            for (var i = []; t; t = t.nextSibling) 1 === t.nodeType && t !== e && i.push(t);
            return i;
        }
        var S = k.expr.match.needsContext;
        function E(t, e) {
            return t.nodeName && t.nodeName.toLowerCase() === e.toLowerCase();
        }
        var A = /^<([a-z][^\/\0>:\x20\t\r\n\f]*)[\x20\t\r\n\f]*\/?>(?:<\/\1>|)$/i;
        function P(t, i, n) {
            return b(i)
                ? k.grep(t, function (t, e) {
                      return !!i.call(t, e, t) !== n;
                  })
                : i.nodeType
                ? k.grep(t, function (t) {
                      return (t === i) !== n;
                  })
                : "string" !==    typeof i
                ? k.grep(t, function (t) {
                      return -1 < s.call(i, t) !== n;
                  })
                : k.filter(i, t, n);
        }
        (k.filter = function (t, e, i) {
            var n = e[0];
            return (
                i && (t = ":not(" + t + ")"),
                1 === e.length && 1 === n.nodeType
                    ? k.find.matchesSelector(n, t)
                        ? [n]
                        : []
                    : k.find.matches(
                          t,
                          k.grep(e, function (t) {
                              return 1 === t.nodeType;
                          })
                      )
            );
        }),
            k.fn.extend({
                find: function (t) {
                    var e,
                        i,
                        n = this.length,
                        s = this;
                    if ("string" !==    typeof t)
                        return this.pushStack(
                            k(t).filter(function () {
                                for (e = 0; e < n; e++) if (k.contains(s[e], this)) return !0;
                            })
                        );
                    for (i = this.pushStack([]), e = 0; e < n; e++) k.find(t, s[e], i);
                    return 1 < n ? k.uniqueSort(i) : i;
                },
                filter: function (t) {
                    return this.pushStack(P(this, t || [], !1));
                },
                not: function (t) {
                    return this.pushStack(P(this, t || [], !0));
                },
                is: function (t) {
                    return !!P(this, "string" === typeof t && S.test(t) ? k(t) : t || [], !1).length;
                },
            });
        var $,
            I = /^(?:\s*(<[\w\W]+>)[^>]*|#([\w-]+))$/;
        ((k.fn.init = function (t, e, i) {
            var n, s;
            if (!t) return this;
            if (((i = i || $), "string" !==    typeof t)) return t.nodeType ? ((this[0] = t), (this.length = 1), this) : b(t) ? (void 0 !== i.ready ? i.ready(t) : t(k)) : k.makeArray(t, this);
            if (!(n = "<" === t[0] && ">" === t[t.length - 1] && 3 <= t.length ? [null, t, null] : I.exec(t)) || (!n[1] && e)) return !e || e.jquery ? (e || i).find(t) : this.constructor(e).find(t);
            if (n[1]) {
                if (((e = e instanceof k ? e[0] : e), k.merge(this, k.parseHTML(n[1], e && e.nodeType ? e.ownerDocument || e : D, !0)), A.test(n[1]) && k.isPlainObject(e))) for (n in e) b(this[n]) ? this[n](e[n]) : this.attr(n, e[n]);
                return this;
            }
            return (s = D.getElementById(n[2])) && ((this[0] = s), (this.length = 1)), this;
        }).prototype = k.fn),
            ($ = k(D));
        var z = /^(?:parents|prev(?:Until|All))/,
            O = { children: !0, contents: !0, next: !0, prev: !0 };
        function N(t, e) {
            for (; (t = t[e]) && 1 !== t.nodeType; );
            return t;
        }
        k.fn.extend({
            has: function (t) {
                var e = k(t, this),
                    i = e.length;
                return this.filter(function () {
                    for (var t = 0; t < i; t++) if (k.contains(this, e[t])) return !0;
                });
            },
            closest: function (t, e) {
                var i,
                    n = 0,
                    s = this.length,
                    o = [],
                    r = "string" !==    typeof t && k(t);
                if (!S.test(t))
                    for (; n < s; n++)
                        for (i = this[n]; i && i !== e; i = i.parentNode)
                            if (i.nodeType < 11 && (r ? -1 < r.index(i) : 1 === i.nodeType && k.find.matchesSelector(i, t))) {
                                o.push(i);
                                break;
                            }
                return this.pushStack(1 < o.length ? k.uniqueSort(o) : o);
            },
            index: function (t) {
                return t ? ("string" === typeof t ? s.call(k(t), this[0]) : s.call(this, t.jquery ? t[0] : t)) : this[0] && this[0].parentNode ? this.first().prevAll().length : -1;
            },
            add: function (t, e) {
                return this.pushStack(k.uniqueSort(k.merge(this.get(), k(t, e))));
            },
            addBack: function (t) {
                return this.add(null === t ? this.prevObject : this.prevObject.filter(t));
            },
        }),
            k.each(
                {
                    parent: function (t) {
                        var e = t.parentNode;
                        return e && 11 !== e.nodeType ? e : null;
                    },
                    parents: function (t) {
                        return x(t, "parentNode");
                    },
                    parentsUntil: function (t, e, i) {
                        return x(t, "parentNode", i);
                    },
                    next: function (t) {
                        return N(t, "nextSibling");
                    },
                    prev: function (t) {
                        return N(t, "previousSibling");
                    },
                    nextAll: function (t) {
                        return x(t, "nextSibling");
                    },
                    prevAll: function (t) {
                        return x(t, "previousSibling");
                    },
                    nextUntil: function (t, e, i) {
                        return x(t, "nextSibling", i);
                    },
                    prevUntil: function (t, e, i) {
                        return x(t, "previousSibling", i);
                    },
                    siblings: function (t) {
                        return T((t.parentNode || {}).firstChild, t);
                    },
                    children: function (t) {
                        return T(t.firstChild);
                    },
                    contents: function (t) {
                        return void 0 !== t.contentDocument ? t.contentDocument : (E(t, "template") && (t = t.content || t), k.merge([], t.childNodes));
                    },
                },
                function (n, s) {
                    k.fn[n] = function (t, e) {
                        var i = k.map(this, s, t);
                        return "Until" !== n.slice(-5) && (e = t), e && "string" === typeof e && (i = k.filter(e, i)), 1 < this.length && (O[n] || k.uniqueSort(i), z.test(n) && i.reverse()), this.pushStack(i);
                    };
                }
            );
        var M = /[^\x20\t\r\n\f]+/g;
        function F(t) {
            return t;
        }
        function L(t) {
            throw t;
        }
        function H(t, e, i, n) {
            var s;
            try {
                t && b((s = t.promise)) ? s.call(t).done(e).fail(i) : t && b((s = t.then)) ? s.call(t, e, i) : e.apply(void 0, [t].slice(n));
            } catch (t) {
                i.apply(void 0, [t]);
            }
        }
        (k.Callbacks = function (n) {
            var t, i;
            n =
                "string" === typeof n
                    ? ((t = n),
                      (i = {}),
                      k.each(t.match(M) || [], function (t, e) {
                          i[e] = !0;
                      }),
                      i)
                    : k.extend({}, n);
            function s() {
                for (a = a || n.once, r = o = !0; c.length; h = -1) for (e = c.shift(); ++h < l.length; ) !1 === l[h].apply(e[0], e[1]) && n.stopOnFalse && ((h = l.length), (e = !1));
                n.memory || (e = !1), (o = !1), a && (l = e ? [] : "");
            }
            var o,
                e,
                r,
                a,
                l = [],
                c = [],
                h = -1,
                u = {
                    add: function () {
                        return (
                            l &&
                                (e && !o && ((h = l.length - 1), c.push(e)),
                                (function i(t) {
                                    k.each(t, function (t, e) {
                                        b(e) ? (n.unique && u.has(e)) || l.push(e) : e && e.length && "string" !== _(e) && i(e);
                                    });
                                })(arguments),
                                e && !o && s()),
                            this
                        );
                    },
                    remove: function () {
                        return (
                            k.each(arguments, function (t, e) {
                                for (var i; -1 < (i = k.inArray(e, l, i)); ) l.splice(i, 1), i <= h && h--;
                            }),
                            this
                        );
                    },
                    has: function (t) {
                        return t ? -1 < k.inArray(t, l) : 0 < l.length;
                    },
                    empty: function () {
                        return (l = l && []), this;
                    },
                    disable: function () {
                        return (a = c = []), (l = e = ""), this;
                    },
                    disabled: function () {
                        return !l;
                    },
                    lock: function () {
                        return (a = c = []), e || o || (l = e = ""), this;
                    },
                    locked: function () {
                        return !!a;
                    },
                    fireWith: function (t, e) {
                        return a || ((e = [t, (e = e || []).slice ? e.slice() : e]), c.push(e), o || s()), this;
                    },
                    fire: function () {
                        return u.fireWith(this, arguments), this;
                    },
                    fired: function () {
                        return !!r;
                    },
                };
            return u;
        }),
            k.extend({
                Deferred: function (t) {
                    var o = [
                            ["notify", "progress", k.Callbacks("memory"), k.Callbacks("memory"), 2],
                            ["resolve", "done", k.Callbacks("once memory"), k.Callbacks("once memory"), 0, "resolved"],
                            ["reject", "fail", k.Callbacks("once memory"), k.Callbacks("once memory"), 1, "rejected"],
                        ],
                        s = "pending",
                        r = {
                            state: function () {
                                return s;
                            },
                            always: function () {
                                return a.done(arguments).fail(arguments), this;
                            },
                            catch: function (t) {
                                return r.then(null, t);
                            },
                            pipe: function () {
                                var s = arguments;
                                return k
                                    .Deferred(function (n) {
                                        k.each(o, function (t, e) {
                                            var i = b(s[e[4]]) && s[e[4]];
                                            a[e[1]](function () {
                                                var t = i && i.apply(this, arguments);
                                                t && b(t.promise) ? t.promise().progress(n.notify).done(n.resolve).fail(n.reject) : n[e[0] + "With"](this, i ? [t] : arguments);
                                            });
                                        }),
                                            (s = null);
                                    })
                                    .promise();
                            },
                            then: function (e, i, n) {
                                var l = 0;
                                function c(s, o, r, a) {
                                    return function () {
                                        function t() {
                                            var t, e;
                                            if (!(s < l)) {
                                                if ((t = r.apply(i, n)) === o.promise()) throw new TypeError("Thenable self-resolution");
                                                (e = t && ("object" === typeof t || "function" === typeof t) && t.then),
                                                    b(e)
                                                        ? a
                                                            ? e.call(t, c(l, o, F, a), c(l, o, L, a))
                                                            : (l++, e.call(t, c(l, o, F, a), c(l, o, L, a), c(l, o, F, o.notifyWith)))
                                                        : (r !== F && ((i = void 0), (n = [t])), (a || o.resolveWith)(i, n));
                                            }
                                        }
                                        var i = this,
                                            n = arguments,
                                            e = a
                                                ? t
                                                : function () {
                                                      try {
                                                          t();
                                                      } catch (t) {
                                                          k.Deferred.exceptionHook && k.Deferred.exceptionHook(t, e.stackTrace), l <= s + 1 && (r !== L && ((i = void 0), (n = [t])), o.rejectWith(i, n));
                                                      }
                                                  };
                                        s ? e() : (k.Deferred.getStackHook && (e.stackTrace = k.Deferred.getStackHook()), C.setTimeout(e));
                                    };
                                }
                                return k
                                    .Deferred(function (t) {
                                        o[0][3].add(c(0, t, b(n) ? n : F, t.notifyWith)), o[1][3].add(c(0, t, b(e) ? e : F)), o[2][3].add(c(0, t, b(i) ? i : L));
                                    })
                                    .promise();
                            },
                            promise: function (t) {
                                return null !==    t ? k.extend(t, r) : r;
                            },
                        },
                        a = {};
                    return (
                        k.each(o, function (t, e) {
                            var i = e[2],
                                n = e[5];
                            (r[e[1]] = i.add),
                                n &&
                                    i.add(
                                        function () {
                                            s = n;
                                        },
                                        o[3 - t][2].disable,
                                        o[3 - t][3].disable,
                                        o[0][2].lock,
                                        o[0][3].lock
                                    ),
                                i.add(e[3].fire),
                                (a[e[0]] = function () {
                                    return a[e[0] + "With"](this === a ? void 0 : this, arguments), this;
                                }),
                                (a[e[0] + "With"] = i.fireWith);
                        }),
                        r.promise(a),
                        t && t.call(a, a),
                        a
                    );
                },
                when: function (t) {
                    function e(e) {
                        return function (t) {
                            (s[e] = this), (o[e] = 1 < arguments.length ? a.call(arguments) : t), --i || r.resolveWith(s, o);
                        };
                    }
                    var i = arguments.length,
                        n = i,
                        s = Array(n),
                        o = a.call(arguments),
                        r = k.Deferred();
                    if (i <= 1 && (H(t, r.done(e(n)).resolve, r.reject, !i), "pending" === r.state() || b(o[n] && o[n].then))) return r.then();
                    for (; n--; ) H(o[n], e(n), r.reject);
                    return r.promise();
                },
            });
        var R = /^(Eval|Internal|Range|Reference|Syntax|Type|URI)Error$/;
        (k.Deferred.exceptionHook = function (t, e) {
            C.console && C.console.warn && t && R.test(t.name) && C.console.warn("jQuery.Deferred exception: " + t.message, t.stack, e);
        }),
            (k.readyException = function (t) {
                C.setTimeout(function () {
                    throw t;
                });
            });
        var j = k.Deferred();
        function U() {
            D.removeEventListener("DOMContentLoaded", U), C.removeEventListener("load", U), k.ready();
        }
        (k.fn.ready = function (t) {
            return (
                j.then(t).catch(function (t) {
                    k.readyException(t);
                }),
                this
            );
        }),
            k.extend({
                isReady: !1,
                readyWait: 1,
                ready: function (t) {
                    (!0 === t ? --k.readyWait : k.isReady) || ((k.isReady = !0) !== t && 0 < --k.readyWait) || j.resolveWith(D, [k]);
                },
            }),
            (k.ready.then = j.then),
            "complete" === D.readyState || ("loading" !== D.readyState && !D.documentElement.doScroll) ? C.setTimeout(k.ready) : (D.addEventListener("DOMContentLoaded", U), C.addEventListener("load", U));
        var W = function (t, e, i, n, s, o, r) {
                var a = 0,
                    l = t.length,
                    c = null === i;
                if ("object" === _(i)) for (a in ((s = !0), i)) W(t, e, a, i[a], !0, o, r);
                else if (
                    void 0 !== n &&
                    ((s = !0),
                    b(n) || (r = !0),
                    c &&
                        (e = r
                            ? (e.call(t, n), null)
                            : ((c = e),
                              function (t, e, i) {
                                  return c.call(k(t), i);
                              })),
                    e)
                )
                    for (; a < l; a++) e(t[a], i, r ? n : n.call(t[a], a, e(t[a], i)));
                return s ? t : c ? e.call(t) : l ? e(t[0], i) : o;
            },
            q = /^-ms-/,
            B = /-([a-z])/g;
        function Y(t, e) {
            return e.toUpperCase();
        }
        function V(t) {
            return t.replace(q, "ms-").replace(B, Y);
        }
        function X(t) {
            return 1 === t.nodeType || 9 === t.nodeType || !+t.nodeType;
        }
        function G() {
            this.expando = k.expando + G.uid++;
        }
        (G.uid = 1),
            (G.prototype = {
                cache: function (t) {
                    var e = t[this.expando];
                    return e || ((e = {}), X(t) && (t.nodeType ? (t[this.expando] = e) : Object.defineProperty(t, this.expando, { value: e, configurable: !0 }))), e;
                },
                set: function (t, e, i) {
                    var n,
                        s = this.cache(t);
                    if ("string" === typeof e) s[V(e)] = i;
                    else for (n in e) s[V(n)] = e[n];
                    return s;
                },
                get: function (t, e) {
                    return void 0 === e ? this.cache(t) : t[this.expando] && t[this.expando][V(e)];
                },
                access: function (t, e, i) {
                    return void 0 === e || (e && "string" === typeof e && void 0 === i) ? this.get(t, e) : (this.set(t, e, i), void 0 !== i ? i : e);
                },
                remove: function (t, e) {
                    var i,
                        n = t[this.expando];
                    if (void 0 !== n) {
                        if (void 0 !== e) {
                            i = (e = Array.isArray(e) ? e.map(V) : (e = V(e)) in n ? [e] : e.match(M) || []).length;
                            for (; i--; ) delete n[e[i]];
                        }
                        (void 0 !== e && !k.isEmptyObject(n)) || (t.nodeType ? (t[this.expando] = void 0) : delete t[this.expando]);
                    }
                },
                hasData: function (t) {
                    var e = t[this.expando];
                    return void 0 !== e && !k.isEmptyObject(e);
                },
            });
        var Q = new G(),
            K = new G(),
            Z = /^(?:\{[\w\W]*\}|\[[\w\W]*\])$/,
            J = /[A-Z]/g;
        function tt(t, e, i) {
            var n, s;
            if (void 0 === i && 1 === t.nodeType)
                if (((n = "data-" + e.replace(J, "-$&").toLowerCase()), "string" === typeof (i = t.getAttribute(n)))) {
                    try {
                        i = "true" === (s = i) || ("false" !== s && ("null" === s ? null : s === +s + "" ? +s : Z.test(s) ? JSON.parse(s) : s));
                    } catch (t) {}
                    K.set(t, e, i);
                } else i = void 0;
            return i;
        }
        k.extend({
            hasData: function (t) {
                return K.hasData(t) || Q.hasData(t);
            },
            data: function (t, e, i) {
                return K.access(t, e, i);
            },
            removeData: function (t, e) {
                K.remove(t, e);
            },
            _data: function (t, e, i) {
                return Q.access(t, e, i);
            },
            _removeData: function (t, e) {
                Q.remove(t, e);
            },
        }),
            k.fn.extend({
                data: function (i, t) {
                    var e,
                        n,
                        s,
                        o = this[0],
                        r = o && o.attributes;
                    if (void 0 !== i)
                        return "object" === typeof i
                            ? this.each(function () {
                                  K.set(this, i);
                              })
                            : W(
                                  this,
                                  function (t) {
                                      var e;
                                      if (o && void 0 === t) return void 0 !== (e = K.get(o, i)) || void 0 !== (e = tt(o, i)) ? e : void 0;
                                      this.each(function () {
                                          K.set(this, i, t);
                                      });
                                  },
                                  null,
                                  t,
                                  1 < arguments.length,
                                  null,
                                  !0
                              );
                    if (this.length && ((s = K.get(o)), 1 === o.nodeType && !Q.get(o, "hasDataAttrs"))) {
                        for (e = r.length; e--; ) r[e] && 0 === (n = r[e].name).indexOf("data-") && ((n = V(n.slice(5))), tt(o, n, s[n]));
                        Q.set(o, "hasDataAttrs", !0);
                    }
                    return s;
                },
                removeData: function (t) {
                    return this.each(function () {
                        K.remove(this, t);
                    });
                },
            }),
            k.extend({
                queue: function (t, e, i) {
                    var n;
                    if (t) return (e = (e || "fx") + "queue"), (n = Q.get(t, e)), i && (!n || Array.isArray(i) ? (n = Q.access(t, e, k.makeArray(i))) : n.push(i)), n || [];
                },
                dequeue: function (t, e) {
                    e = e || "fx";
                    var i = k.queue(t, e),
                        n = i.length,
                        s = i.shift(),
                        o = k._queueHooks(t, e);
                    "inprogress" === s && ((s = i.shift()), n--),
                        s &&
                            ("fx" === e && i.unshift("inprogress"),
                            delete o.stop,
                            s.call(
                                t,
                                function () {
                                    k.dequeue(t, e);
                                },
                                o
                            )),
                        !n && o && o.empty.fire();
                },
                _queueHooks: function (t, e) {
                    var i = e + "queueHooks";
                    return (
                        Q.get(t, i) ||
                        Q.access(t, i, {
                            empty: k.Callbacks("once memory").add(function () {
                                Q.remove(t, [e + "queue", i]);
                            }),
                        })
                    );
                },
            }),
            k.fn.extend({
                queue: function (e, i) {
                    var t = 2;
                    return (
                        "string" !==    typeof e && ((i = e), (e = "fx"), t--),
                        arguments.length < t
                            ? k.queue(this[0], e)
                            : void 0 === i
                            ? this
                            : this.each(function () {
                                  var t = k.queue(this, e, i);
                                  k._queueHooks(this, e), "fx" === e && "inprogress" !== t[0] && k.dequeue(this, e);
                              })
                    );
                },
                dequeue: function (t) {
                    return this.each(function () {
                        k.dequeue(this, t);
                    });
                },
                clearQueue: function (t) {
                    return this.queue(t || "fx", []);
                },
                promise: function (t, e) {
                    function i() {
                        --s || o.resolveWith(r, [r]);
                    }
                    var n,
                        s = 1,
                        o = k.Deferred(),
                        r = this,
                        a = this.length;
                    for ("string" !==    typeof t && ((e = t), (t = void 0)), t = t || "fx"; a--; ) (n = Q.get(r[a], t + "queueHooks")) && n.empty && (s++, n.empty.add(i));
                    return i(), o.promise(e);
                },
            });
        var et = /[+-]?(?:\d*\.|)\d+(?:[eE][+-]?\d+|)/.source,
            it = new RegExp("^(?:([+-])=|)(" + et + ")([a-z%]*)$", "i"),
            nt = ["Top", "Right", "Bottom", "Left"],
            st = D.documentElement,
            ot = function (t) {
                return k.contains(t.ownerDocument, t);
            },
            rt = { composed: !0 };
        st.getRootNode &&
            (ot = function (t) {
                return k.contains(t.ownerDocument, t) || t.getRootNode(rt) === t.ownerDocument;
            });
        function at(t, e, i, n) {
            var s,
                o,
                r = {};
            for (o in e) (r[o] = t.style[o]), (t.style[o] = e[o]);
            for (o in ((s = i.apply(t, n || [])), e)) t.style[o] = r[o];
            return s;
        }
        var lt = function (t, e) {
            return "none" === (t = e || t).style.display || ("" === t.style.display && ot(t) && "none" === k.css(t, "display"));
        };
        function ct(t, e, i, n) {
            var s,
                o,
                r = 20,
                a = n
                    ? function () {
                          return n.cur();
                      }
                    : function () {
                          return k.css(t, e, "");
                      },
                l = a(),
                c = (i && i[3]) || (k.cssNumber[e] ? "" : "px"),
                h = t.nodeType && (k.cssNumber[e] || ("px" !== c && +l)) && it.exec(k.css(t, e));
            if (h && h[3] !== c) {
                for (l /= 2, c = c || h[3], h = +l || 1; r--; ) k.style(t, e, h + c), (1 - o) * (1 - (o = a() / l || 0.5)) <= 0 && (r = 0), (h /= o);
                (h *= 2), k.style(t, e, h + c), (i = i || []);
            }
            return i && ((h = +h || +l || 0), (s = i[1] ? h + (i[1] + 1) * i[2] : +i[2]), n && ((n.unit = c), (n.start = h), (n.end = s))), s;
        }
        var ht = {};
        function ut(t, e) {
            for (var i, n, s, o, r, a, l, c = [], h = 0, u = t.length; h < u; h++)
                (n = t[h]).style &&
                    ((i = n.style.display),
                    e
                        ? ("none" === i && ((c[h] = Q.get(n, "display") || null), c[h] || (n.style.display = "")),
                          "" === n.style.display &&
                              lt(n) &&
                              (c[h] =
                                  ((l = r = o = void 0),
                                  (r = (s = n).ownerDocument),
                                  (a = s.nodeName),
                                  (l = ht[a]) || ((o = r.body.appendChild(r.createElement(a))), (l = k.css(o, "display")), o.parentNode.removeChild(o), "none" === l && (l = "block"), (ht[a] = l)))))
                        : "none" !== i && ((c[h] = "none"), Q.set(n, "display", i)));
            for (h = 0; h < u; h++) null !==    c[h] && (t[h].style.display = c[h]);
            return t;
        }
        k.fn.extend({
            show: function () {
                return ut(this, !0);
            },
            hide: function () {
                return ut(this);
            },
            toggle: function (t) {
                return "boolean" === typeof t
                    ? t
                        ? this.show()
                        : this.hide()
                    : this.each(function () {
                          lt(this) ? k(this).show() : k(this).hide();
                      });
            },
        });
        var dt = /^(?:checkbox|radio)$/i,
            pt = /<([a-z][^\/\0>\x20\t\r\n\f]*)/i,
            ft = /^$|^module$|\/(?:java|ecma)script/i,
            gt = {
                option: [1, "<select multiple='multiple'>", "</select>"],
                thead: [1, "<table>", "</table>"],
                col: [2, "<table><colgroup>", "</colgroup></table>"],
                tr: [2, "<table><tbody>", "</tbody></table>"],
                td: [3, "<table><tbody><tr>", "</tr></tbody></table>"],
                _default: [0, "", ""],
            };
        function mt(t, e) {
            var i;
            return (i = void 0 !== t.getElementsByTagName ? t.getElementsByTagName(e || "*") : void 0 !== t.querySelectorAll ? t.querySelectorAll(e || "*") : []), void 0 === e || (e && E(t, e)) ? k.merge([t], i) : i;
        }
        function vt(t, e) {
            for (var i = 0, n = t.length; i < n; i++) Q.set(t[i], "globalEval", !e || Q.get(e[i], "globalEval"));
        }
        (gt.optgroup = gt.option), (gt.tbody = gt.tfoot = gt.colgroup = gt.caption = gt.thead), (gt.th = gt.td);
        var yt,
            bt,
            wt = /<|&#?\w+;/;
        function _t(t, e, i, n, s) {
            for (var o, r, a, l, c, h, u = e.createDocumentFragment(), d = [], p = 0, f = t.length; p < f; p++)
                if ((o = t[p]) || 0 === o)
                    if ("object" === _(o)) k.merge(d, o.nodeType ? [o] : o);
                    else if (wt.test(o)) {
                        for (r = r || u.appendChild(e.createElement("div")), a = (pt.exec(o) || ["", ""])[1].toLowerCase(), l = gt[a] || gt._default, r.innerHTML = l[1] + k.htmlPrefilter(o) + l[2], h = l[0]; h--; ) r = r.lastChild;
                        k.merge(d, r.childNodes), ((r = u.firstChild).textContent = "");
                    } else d.push(e.createTextNode(o));
            for (u.textContent = "", p = 0; (o = d[p++]); )
                if (n && -1 < k.inArray(o, n)) s && s.push(o);
                else if (((c = ot(o)), (r = mt(u.appendChild(o), "script")), c && vt(r), i)) for (h = 0; (o = r[h++]); ) ft.test(o.type || "") && i.push(o);
            return u;
        }
        (yt = D.createDocumentFragment().appendChild(D.createElement("div"))),
            (bt = D.createElement("input")).setAttribute("type", "radio"),
            bt.setAttribute("checked", "checked"),
            bt.setAttribute("name", "t"),
            yt.appendChild(bt),
            (y.checkClone = yt.cloneNode(!0).cloneNode(!0).lastChild.checked),
            (yt.innerHTML = "<textarea>x</textarea>"),
            (y.noCloneChecked = !!yt.cloneNode(!0).lastChild.defaultValue);
        var xt = /^key/,
            Ct = /^(?:mouse|pointer|contextmenu|drag|drop)|click/,
            Dt = /^([^.]*)(?:\.(.+)|)/;
        function kt() {
            return !0;
        }
        function Tt() {
            return !1;
        }
        function St(t, e) {
            return (
                (t ===
                    (function () {
                        try {
                            return D.activeElement;
                        } catch (t) {}
                    })()) ==
                ("focus" === e)
            );
        }
        function Et(t, e, i, n, s, o) {
            var r, a;
            if ("object" === typeof e) {
                for (a in ("string" !==    typeof i && ((n = n || i), (i = void 0)), e)) Et(t, a, i, n, e[a], o);
                return t;
            }
            if ((null === n && null === s ? ((s = i), (n = i = void 0)) : null === s && ("string" === typeof i ? ((s = n), (n = void 0)) : ((s = n), (n = i), (i = void 0))), !1 === s)) s = Tt;
            else if (!s) return t;
            return (
                1 === o &&
                    ((r = s),
                    ((s = function (t) {
                        return k().off(t), r.apply(this, arguments);
                    }).guid = r.guid || (r.guid = k.guid++))),
                t.each(function () {
                    k.event.add(this, e, s, n, i);
                })
            );
        }
        function At(t, s, o) {
            o
                ? (Q.set(t, s, !1),
                  k.event.add(t, s, {
                      namespace: !1,
                      handler: function (t) {
                          var e,
                              i,
                              n = Q.get(this, s);
                          if (1 & t.isTrigger && this[s]) {
                              if (n.length) (k.event.special[s] || {}).delegateType && t.stopPropagation();
                              else if (((n = a.call(arguments)), Q.set(this, s, n), (e = o(this, s)), this[s](), n !== (i = Q.get(this, s)) || e ? Q.set(this, s, !1) : (i = {}), n !== i))
                                  return t.stopImmediatePropagation(), t.preventDefault(), i.value;
                          } else n.length && (Q.set(this, s, { value: k.event.trigger(k.extend(n[0], k.Event.prototype), n.slice(1), this) }), t.stopImmediatePropagation());
                      },
                  }))
                : void 0 === Q.get(t, s) && k.event.add(t, s, kt);
        }
        (k.event = {
            global: {},
            add: function (e, t, i, n, s) {
                var o,
                    r,
                    a,
                    l,
                    c,
                    h,
                    u,
                    d,
                    p,
                    f,
                    g,
                    m = Q.get(e);
                if (m)
                    for (
                        i.handler && ((i = (o = i).handler), (s = o.selector)),
                            s && k.find.matchesSelector(st, s),
                            i.guid || (i.guid = k.guid++),
                            (l = m.events) || (l = m.events = {}),
                            (r = m.handle) ||
                                (r = m.handle = function (t) {
                                    return void 0 !== k && k.event.triggered !== t.type ? k.event.dispatch.apply(e, arguments) : void 0;
                                }),
                            c = (t = (t || "").match(M) || [""]).length;
                        c--;

                    )
                        (p = g = (a = Dt.exec(t[c]) || [])[1]),
                            (f = (a[2] || "").split(".").sort()),
                            p &&
                                ((u = k.event.special[p] || {}),
                                (p = (s ? u.delegateType : u.bindType) || p),
                                (u = k.event.special[p] || {}),
                                (h = k.extend({ type: p, origType: g, data: n, handler: i, guid: i.guid, selector: s, needsContext: s && k.expr.match.needsContext.test(s), namespace: f.join(".") }, o)),
                                (d = l[p]) || (((d = l[p] = []).delegateCount = 0), (u.setup && !1 !== u.setup.call(e, n, f, r)) || (e.addEventListener && e.addEventListener(p, r))),
                                u.add && (u.add.call(e, h), h.handler.guid || (h.handler.guid = i.guid)),
                                s ? d.splice(d.delegateCount++, 0, h) : d.push(h),
                                (k.event.global[p] = !0));
            },
            remove: function (t, e, i, n, s) {
                var o,
                    r,
                    a,
                    l,
                    c,
                    h,
                    u,
                    d,
                    p,
                    f,
                    g,
                    m = Q.hasData(t) && Q.get(t);
                if (m && (l = m.events)) {
                    for (c = (e = (e || "").match(M) || [""]).length; c--; )
                        if (((p = g = (a = Dt.exec(e[c]) || [])[1]), (f = (a[2] || "").split(".").sort()), p)) {
                            for (u = k.event.special[p] || {}, d = l[(p = (n ? u.delegateType : u.bindType) || p)] || [], a = a[2] && new RegExp("(^|\\.)" + f.join("\\.(?:.*\\.|)") + "(\\.|$)"), r = o = d.length; o--; )
                                (h = d[o]),
                                    (!s && g !== h.origType) ||
                                        (i && i.guid !== h.guid) ||
                                        (a && !a.test(h.namespace)) ||
                                        (n && n !== h.selector && ("**" !== n || !h.selector)) ||
                                        (d.splice(o, 1), h.selector && d.delegateCount--, u.remove && u.remove.call(t, h));
                            r && !d.length && ((u.teardown && !1 !== u.teardown.call(t, f, m.handle)) || k.removeEvent(t, p, m.handle), delete l[p]);
                        } else for (p in l) k.event.remove(t, p + e[c], i, n, !0);
                    k.isEmptyObject(l) && Q.remove(t, "handle events");
                }
            },
            dispatch: function (t) {
                var e,
                    i,
                    n,
                    s,
                    o,
                    r,
                    a = k.event.fix(t),
                    l = new Array(arguments.length),
                    c = (Q.get(this, "events") || {})[a.type] || [],
                    h = k.event.special[a.type] || {};
                for (l[0] = a, e = 1; e < arguments.length; e++) l[e] = arguments[e];
                if (((a.delegateTarget = this), !h.preDispatch || !1 !== h.preDispatch.call(this, a))) {
                    for (r = k.event.handlers.call(this, a, c), e = 0; (s = r[e++]) && !a.isPropagationStopped(); )
                        for (a.currentTarget = s.elem, i = 0; (o = s.handlers[i++]) && !a.isImmediatePropagationStopped(); )
                            (a.rnamespace && !1 !== o.namespace && !a.rnamespace.test(o.namespace)) ||
                                ((a.handleObj = o), (a.data = o.data), void 0 !== (n = ((k.event.special[o.origType] || {}).handle || o.handler).apply(s.elem, l)) && !1 === (a.result = n) && (a.preventDefault(), a.stopPropagation()));
                    return h.postDispatch && h.postDispatch.call(this, a), a.result;
                }
            },
            handlers: function (t, e) {
                var i,
                    n,
                    s,
                    o,
                    r,
                    a = [],
                    l = e.delegateCount,
                    c = t.target;
                if (l && c.nodeType && !("click" === t.type && 1 <= t.button))
                    for (; c !== this; c = c.parentNode || this)
                        if (1 === c.nodeType && ("click" !== t.type || !0 !== c.disabled)) {
                            for (o = [], r = {}, i = 0; i < l; i++) void 0 === r[(s = (n = e[i]).selector + " ")] && (r[s] = n.needsContext ? -1 < k(s, this).index(c) : k.find(s, this, null, [c]).length), r[s] && o.push(n);
                            o.length && a.push({ elem: c, handlers: o });
                        }
                return (c = this), l < e.length && a.push({ elem: c, handlers: e.slice(l) }), a;
            },
            addProp: function (e, t) {
                Object.defineProperty(k.Event.prototype, e, {
                    enumerable: !0,
                    configurable: !0,
                    get: b(t)
                        ? function () {
                              if (this.originalEvent) return t(this.originalEvent);
                          }
                        : function () {
                              if (this.originalEvent) return this.originalEvent[e];
                          },
                    set: function (t) {
                        Object.defineProperty(this, e, { enumerable: !0, configurable: !0, writable: !0, value: t });
                    },
                });
            },
            fix: function (t) {
                return t[k.expando] ? t : new k.Event(t);
            },
            special: {
                load: { noBubble: !0 },
                click: {
                    setup: function (t) {
                        var e = this || t;
                        return dt.test(e.type) && e.click && E(e, "input") && At(e, "click", kt), !1;
                    },
                    trigger: function (t) {
                        var e = this || t;
                        return dt.test(e.type) && e.click && E(e, "input") && At(e, "click"), !0;
                    },
                    _default: function (t) {
                        var e = t.target;
                        return (dt.test(e.type) && e.click && E(e, "input") && Q.get(e, "click")) || E(e, "a");
                    },
                },
                beforeunload: {
                    postDispatch: function (t) {
                        void 0 !== t.result && t.originalEvent && (t.originalEvent.returnValue = t.result);
                    },
                },
            },
        }),
            (k.removeEvent = function (t, e, i) {
                t.removeEventListener && t.removeEventListener(e, i);
            }),
            (k.Event = function (t, e) {
                if (!(this instanceof k.Event)) return new k.Event(t, e);
                t && t.type
                    ? ((this.originalEvent = t),
                      (this.type = t.type),
                      (this.isDefaultPrevented = t.defaultPrevented || (void 0 === t.defaultPrevented && !1 === t.returnValue) ? kt : Tt),
                      (this.target = t.target && 3 === t.target.nodeType ? t.target.parentNode : t.target),
                      (this.currentTarget = t.currentTarget),
                      (this.relatedTarget = t.relatedTarget))
                    : (this.type = t),
                    e && k.extend(this, e),
                    (this.timeStamp = (t && t.timeStamp) || Date.now()),
                    (this[k.expando] = !0);
            }),
            (k.Event.prototype = {
                constructor: k.Event,
                isDefaultPrevented: Tt,
                isPropagationStopped: Tt,
                isImmediatePropagationStopped: Tt,
                isSimulated: !1,
                preventDefault: function () {
                    var t = this.originalEvent;
                    (this.isDefaultPrevented = kt), t && !this.isSimulated && t.preventDefault();
                },
                stopPropagation: function () {
                    var t = this.originalEvent;
                    (this.isPropagationStopped = kt), t && !this.isSimulated && t.stopPropagation();
                },
                stopImmediatePropagation: function () {
                    var t = this.originalEvent;
                    (this.isImmediatePropagationStopped = kt), t && !this.isSimulated && t.stopImmediatePropagation(), this.stopPropagation();
                },
            }),
            k.each(
                {
                    altKey: !0,
                    bubbles: !0,
                    cancelable: !0,
                    changedTouches: !0,
                    ctrlKey: !0,
                    detail: !0,
                    eventPhase: !0,
                    metaKey: !0,
                    pageX: !0,
                    pageY: !0,
                    shiftKey: !0,
                    view: !0,
                    char: !0,
                    code: !0,
                    charCode: !0,
                    key: !0,
                    keyCode: !0,
                    button: !0,
                    buttons: !0,
                    clientX: !0,
                    clientY: !0,
                    offsetX: !0,
                    offsetY: !0,
                    pointerId: !0,
                    pointerType: !0,
                    screenX: !0,
                    screenY: !0,
                    targetTouches: !0,
                    toElement: !0,
                    touches: !0,
                    which: function (t) {
                        var e = t.button;
                        return null === t.which && xt.test(t.type) ? (null !==    t.charCode ? t.charCode : t.keyCode) : !t.which && void 0 !== e && Ct.test(t.type) ? (1 & e ? 1 : 2 & e ? 3 : 4 & e ? 2 : 0) : t.which;
                    },
                },
                k.event.addProp
            ),
            k.each({ focus: "focusin", blur: "focusout" }, function (t, e) {
                k.event.special[t] = {
                    setup: function () {
                        return At(this, t, St), !1;
                    },
                    trigger: function () {
                        return At(this, t), !0;
                    },
                    delegateType: e,
                };
            }),
            k.each({ mouseenter: "mouseover", mouseleave: "mouseout", pointerenter: "pointerover", pointerleave: "pointerout" }, function (t, s) {
                k.event.special[t] = {
                    delegateType: s,
                    bindType: s,
                    handle: function (t) {
                        var e,
                            i = t.relatedTarget,
                            n = t.handleObj;
                        return (i && (i === this || k.contains(this, i))) || ((t.type = n.origType), (e = n.handler.apply(this, arguments)), (t.type = s)), e;
                    },
                };
            }),
            k.fn.extend({
                on: function (t, e, i, n) {
                    return Et(this, t, e, i, n);
                },
                one: function (t, e, i, n) {
                    return Et(this, t, e, i, n, 1);
                },
                off: function (t, e, i) {
                    var n, s;
                    if (t && t.preventDefault && t.handleObj) return (n = t.handleObj), k(t.delegateTarget).off(n.namespace ? n.origType + "." + n.namespace : n.origType, n.selector, n.handler), this;
                    if ("object" !==    typeof t)
                        return (
                            (!1 !== e && "function" !==    typeof e) || ((i = e), (e = void 0)),
                            !1 === i && (i = Tt),
                            this.each(function () {
                                k.event.remove(this, t, i, e);
                            })
                        );
                    for (s in t) this.off(s, e, t[s]);
                    return this;
                },
            });
        var Pt = /<(?!area|br|col|embed|hr|img|input|link|meta|param)(([a-z][^\/\0>\x20\t\r\n\f]*)[^>]*)\/>/gi,
            $t = /<script|<style|<link/i,
            It = /checked\s*(?:[^=]|=\s*.checked.)/i,
            zt = /^\s*<!(?:\[CDATA\[|--)|(?:\]\]|--)>\s*$/g;
        function Ot(t, e) {
            return (E(t, "table") && E(11 !== e.nodeType ? e : e.firstChild, "tr") && k(t).children("tbody")[0]) || t;
        }
        function Nt(t) {
            return (t.type = (null !== t.getAttribute("type")) + "/" + t.type), t;
        }
        function Mt(t) {
            return "true/" === (t.type || "").slice(0, 5) ? (t.type = t.type.slice(5)) : t.removeAttribute("type"), t;
        }
        function Ft(t, e) {
            var i, n, s, o, r, a, l, c;
            if (1 === e.nodeType) {
                if (Q.hasData(t) && ((o = Q.access(t)), (r = Q.set(e, o)), (c = o.events))) for (s in (delete r.handle, (r.events = {}), c)) for (i = 0, n = c[s].length; i < n; i++) k.event.add(e, s, c[s][i]);
                K.hasData(t) && ((a = K.access(t)), (l = k.extend({}, a)), K.set(e, l));
            }
        }
        function Lt(i, n, s, o) {
            n = m.apply([], n);
            var t,
                e,
                r,
                a,
                l,
                c,
                h = 0,
                u = i.length,
                d = u - 1,
                p = n[0],
                f = b(p);
            if (f || (1 < u && "string" === typeof p && !y.checkClone && It.test(p)))
                return i.each(function (t) {
                    var e = i.eq(t);
                    f && (n[0] = p.call(this, t, e.html())), Lt(e, n, s, o);
                });
            if (u && ((e = (t = _t(n, i[0].ownerDocument, !1, i, o)).firstChild), 1 === t.childNodes.length && (t = e), e || o)) {
                for (a = (r = k.map(mt(t, "script"), Nt)).length; h < u; h++) (l = t), h !== d && ((l = k.clone(l, !0, !0)), a && k.merge(r, mt(l, "script"))), s.call(i[h], l, h);
                if (a)
                    for (c = r[r.length - 1].ownerDocument, k.map(r, Mt), h = 0; h < a; h++)
                        (l = r[h]),
                            ft.test(l.type || "") &&
                                !Q.access(l, "globalEval") &&
                                k.contains(c, l) &&
                                (l.src && "module" !== (l.type || "").toLowerCase() ? k._evalUrl && !l.noModule && k._evalUrl(l.src, { nonce: l.nonce || l.getAttribute("nonce") }) : w(l.textContent.replace(zt, ""), l, c));
            }
            return i;
        }
        function Ht(t, e, i) {
            for (var n, s = e ? k.filter(e, t) : t, o = 0; null !==    (n = s[o]); o++) i || 1 !== n.nodeType || k.cleanData(mt(n)), n.parentNode && (i && ot(n) && vt(mt(n, "script")), n.parentNode.removeChild(n));
            return t;
        }
        k.extend({
            htmlPrefilter: function (t) {
                return t.replace(Pt, "<$1></$2>");
            },
            clone: function (t, e, i) {
                var n,
                    s,
                    o,
                    r,
                    a,
                    l,
                    c,
                    h = t.cloneNode(!0),
                    u = ot(t);
                if (!(y.noCloneChecked || (1 !== t.nodeType && 11 !== t.nodeType) || k.isXMLDoc(t)))
                    for (r = mt(h), n = 0, s = (o = mt(t)).length; n < s; n++)
                        (a = o[n]), (l = r[n]), "input" === (c = l.nodeName.toLowerCase()) && dt.test(a.type) ? (l.checked = a.checked) : ("input" !== c && "textarea" !== c) || (l.defaultValue = a.defaultValue);
                if (e)
                    if (i) for (o = o || mt(t), r = r || mt(h), n = 0, s = o.length; n < s; n++) Ft(o[n], r[n]);
                    else Ft(t, h);
                return 0 < (r = mt(h, "script")).length && vt(r, !u && mt(t, "script")), h;
            },
            cleanData: function (t) {
                for (var e, i, n, s = k.event.special, o = 0; void 0 !== (i = t[o]); o++)
                    if (X(i)) {
                        if ((e = i[Q.expando])) {
                            if (e.events) for (n in e.events) s[n] ? k.event.remove(i, n) : k.removeEvent(i, n, e.handle);
                            i[Q.expando] = void 0;
                        }
                        i[K.expando] && (i[K.expando] = void 0);
                    }
            },
        }),
            k.fn.extend({
                detach: function (t) {
                    return Ht(this, t, !0);
                },
                remove: function (t) {
                    return Ht(this, t);
                },
                text: function (t) {
                    return W(
                        this,
                        function (t) {
                            return void 0 === t
                                ? k.text(this)
                                : this.empty().each(function () {
                                      (1 !== this.nodeType && 11 !== this.nodeType && 9 !== this.nodeType) || (this.textContent = t);
                                  });
                        },
                        null,
                        t,
                        arguments.length
                    );
                },
                append: function () {
                    return Lt(this, arguments, function (t) {
                        (1 !== this.nodeType && 11 !== this.nodeType && 9 !== this.nodeType) || Ot(this, t).appendChild(t);
                    });
                },
                prepend: function () {
                    return Lt(this, arguments, function (t) {
                        if (1 === this.nodeType || 11 === this.nodeType || 9 === this.nodeType) {
                            var e = Ot(this, t);
                            e.insertBefore(t, e.firstChild);
                        }
                    });
                },
                before: function () {
                    return Lt(this, arguments, function (t) {
                        this.parentNode && this.parentNode.insertBefore(t, this);
                    });
                },
                after: function () {
                    return Lt(this, arguments, function (t) {
                        this.parentNode && this.parentNode.insertBefore(t, this.nextSibling);
                    });
                },
                empty: function () {
                    for (var t, e = 0; null !==    (t = this[e]); e++) 1 === t.nodeType && (k.cleanData(mt(t, !1)), (t.textContent = ""));
                    return this;
                },
                clone: function (t, e) {
                    return (
                        (t = null !==    t && t),
                        (e = null === e ? t : e),
                        this.map(function () {
                            return k.clone(this, t, e);
                        })
                    );
                },
                html: function (t) {
                    return W(
                        this,
                        function (t) {
                            var e = this[0] || {},
                                i = 0,
                                n = this.length;
                            if (void 0 === t && 1 === e.nodeType) return e.innerHTML;
                            if ("string" === typeof t && !$t.test(t) && !gt[(pt.exec(t) || ["", ""])[1].toLowerCase()]) {
                                t = k.htmlPrefilter(t);
                                try {
                                    for (; i < n; i++) 1 === (e = this[i] || {}).nodeType && (k.cleanData(mt(e, !1)), (e.innerHTML = t));
                                    e = 0;
                                } catch (t) {}
                            }
                            e && this.empty().append(t);
                        },
                        null,
                        t,
                        arguments.length
                    );
                },
                replaceWith: function () {
                    var i = [];
                    return Lt(
                        this,
                        arguments,
                        function (t) {
                            var e = this.parentNode;
                            k.inArray(this, i) < 0 && (k.cleanData(mt(this)), e && e.replaceChild(t, this));
                        },
                        i
                    );
                },
            }),
            k.each({ appendTo: "append", prependTo: "prepend", insertBefore: "before", insertAfter: "after", replaceAll: "replaceWith" }, function (t, r) {
                k.fn[t] = function (t) {
                    for (var e, i = [], n = k(t), s = n.length - 1, o = 0; o <= s; o++) (e = o === s ? this : this.clone(!0)), k(n[o])[r](e), l.apply(i, e.get());
                    return this.pushStack(i);
                };
            });
        var Rt,
            jt,
            Ut,
            Wt,
            qt,
            Bt,
            Yt,
            Vt = new RegExp("^(" + et + ")(?!px)[a-z%]+$", "i"),
            Xt = function (t) {
                var e = t.ownerDocument.defaultView;
                return (e && e.opener) || (e = C), e.getComputedStyle(t);
            },
            Gt = new RegExp(nt.join("|"), "i");
        function Qt() {
            if (Yt) {
                (Bt.style.cssText = "position:absolute;left:-11111px;width:60px;margin-top:1px;padding:0;border:0"),
                    (Yt.style.cssText = "position:relative;display:block;box-sizing:border-box;overflow:scroll;margin:auto;border:1px;padding:1px;width:60%;top:1%"),
                    st.appendChild(Bt).appendChild(Yt);
                var t = C.getComputedStyle(Yt);
                (Rt = "1%" !== t.top),
                    (qt = 12 === Kt(t.marginLeft)),
                    (Yt.style.right = "60%"),
                    (Wt = 36 === Kt(t.right)),
                    (jt = 36 === Kt(t.width)),
                    (Yt.style.position = "absolute"),
                    (Ut = 12 === Kt(Yt.offsetWidth / 3)),
                    st.removeChild(Bt),
                    (Yt = null);
            }
        }
        function Kt(t) {
            return Math.round(parseFloat(t));
        }
        function Zt(t, e, i) {
            var n,
                s,
                o,
                r,
                a = t.style;
            return (
                (i = i || Xt(t)) &&
                    ("" !== (r = i.getPropertyValue(e) || i[e]) || ot(t) || (r = k.style(t, e)),
                    !y.pixelBoxStyles() && Vt.test(r) && Gt.test(e) && ((n = a.width), (s = a.minWidth), (o = a.maxWidth), (a.minWidth = a.maxWidth = a.width = r), (r = i.width), (a.width = n), (a.minWidth = s), (a.maxWidth = o))),
                void 0 !== r ? r + "" : r
            );
        }
        function Jt(t, e) {
            return {
                get: function () {
                    if (!t()) return (this.get = e).apply(this, arguments);
                    delete this.get;
                },
            };
        }
        (Bt = D.createElement("div")),
            (Yt = D.createElement("div")).style &&
                ((Yt.style.backgroundClip = "content-box"),
                (Yt.cloneNode(!0).style.backgroundClip = ""),
                (y.clearCloneStyle = "content-box" === Yt.style.backgroundClip),
                k.extend(y, {
                    boxSizingReliable: function () {
                        return Qt(), jt;
                    },
                    pixelBoxStyles: function () {
                        return Qt(), Wt;
                    },
                    pixelPosition: function () {
                        return Qt(), Rt;
                    },
                    reliableMarginLeft: function () {
                        return Qt(), qt;
                    },
                    scrollboxSize: function () {
                        return Qt(), Ut;
                    },
                }));
        var te = ["Webkit", "Moz", "ms"],
            ee = D.createElement("div").style,
            ie = {};
        function ne(t) {
            var e = k.cssProps[t] || ie[t];
            return (
                e ||
                (t in ee
                    ? t
                    : (ie[t] =
                          (function (t) {
                              for (var e = t[0].toUpperCase() + t.slice(1), i = te.length; i--; ) if ((t = te[i] + e) in ee) return t;
                          })(t) || t))
            );
        }
        var se = /^(none|table(?!-c[ea]).+)/,
            oe = /^--/,
            re = { position: "absolute", visibility: "hidden", display: "block" },
            ae = { letterSpacing: "0", fontWeight: "400" };
        function le(t, e, i) {
            var n = it.exec(e);
            return n ? Math.max(0, n[2] - (i || 0)) + (n[3] || "px") : e;
        }
        function ce(t, e, i, n, s, o) {
            var r = "width" === e ? 1 : 0,
                a = 0,
                l = 0;
            if (i === (n ? "border" : "content")) return 0;
            for (; r < 4; r += 2)
                "margin" === i && (l += k.css(t, i + nt[r], !0, s)),
                    n
                        ? ("content" === i && (l -= k.css(t, "padding" + nt[r], !0, s)), "margin" !== i && (l -= k.css(t, "border" + nt[r] + "Width", !0, s)))
                        : ((l += k.css(t, "padding" + nt[r], !0, s)), "padding" !== i ? (l += k.css(t, "border" + nt[r] + "Width", !0, s)) : (a += k.css(t, "border" + nt[r] + "Width", !0, s)));
            return !n && 0 <= o && (l += Math.max(0, Math.ceil(t["offset" + e[0].toUpperCase() + e.slice(1)] - o - l - a - 0.5)) || 0), l;
        }
        function he(t, e, i) {
            var n = Xt(t),
                s = (!y.boxSizingReliable() || i) && "border-box" === k.css(t, "boxSizing", !1, n),
                o = s,
                r = Zt(t, e, n),
                a = "offset" + e[0].toUpperCase() + e.slice(1);
            if (Vt.test(r)) {
                if (!i) return r;
                r = "auto";
            }
            return (
                ((!y.boxSizingReliable() && s) || "auto" === r || (!parseFloat(r) && "inline" === k.css(t, "display", !1, n))) &&
                    t.getClientRects().length &&
                    ((s = "border-box" === k.css(t, "boxSizing", !1, n)), (o = a in t) && (r = t[a])),
                (r = parseFloat(r) || 0) + ce(t, e, i || (s ? "border" : "content"), o, n, r) + "px"
            );
        }
        function ue(t, e, i, n, s) {
            return new ue.prototype.init(t, e, i, n, s);
        }
        k.extend({
            cssHooks: {
                opacity: {
                    get: function (t, e) {
                        if (e) {
                            var i = Zt(t, "opacity");
                            return "" === i ? "1" : i;
                        }
                    },
                },
            },
            cssNumber: {
                animationIterationCount: !0,
                columnCount: !0,
                fillOpacity: !0,
                flexGrow: !0,
                flexShrink: !0,
                fontWeight: !0,
                gridArea: !0,
                gridColumn: !0,
                gridColumnEnd: !0,
                gridColumnStart: !0,
                gridRow: !0,
                gridRowEnd: !0,
                gridRowStart: !0,
                lineHeight: !0,
                opacity: !0,
                order: !0,
                orphans: !0,
                widows: !0,
                zIndex: !0,
                zoom: !0,
            },
            cssProps: {},
            style: function (t, e, i, n) {
                if (t && 3 !== t.nodeType && 8 !== t.nodeType && t.style) {
                    var s,
                        o,
                        r,
                        a = V(e),
                        l = oe.test(e),
                        c = t.style;
                    if ((l || (e = ne(a)), (r = k.cssHooks[e] || k.cssHooks[a]), void 0 === i)) return r && "get" in r && void 0 !== (s = r.get(t, !1, n)) ? s : c[e];
                    "string" === (o = typeof i) && (s = it.exec(i)) && s[1] && ((i = ct(t, e, s)), (o = "number")),
                        null !==    i &&
                            i === i &&
                            ("number" !== o || l || (i += (s && s[3]) || (k.cssNumber[a] ? "" : "px")),
                            y.clearCloneStyle || "" !== i || 0 !== e.indexOf("background") || (c[e] = "inherit"),
                            (r && "set" in r && void 0 === (i = r.set(t, i, n))) || (l ? c.setProperty(e, i) : (c[e] = i)));
                }
            },
            css: function (t, e, i, n) {
                var s,
                    o,
                    r,
                    a = V(e);
                return (
                    oe.test(e) || (e = ne(a)),
                    (r = k.cssHooks[e] || k.cssHooks[a]) && "get" in r && (s = r.get(t, !0, i)),
                    void 0 === s && (s = Zt(t, e, n)),
                    "normal" === s && e in ae && (s = ae[e]),
                    "" === i || i ? ((o = parseFloat(s)), !0 === i || isFinite(o) ? o || 0 : s) : s
                );
            },
        }),
            k.each(["height", "width"], function (t, l) {
                k.cssHooks[l] = {
                    get: function (t, e, i) {
                        if (e)
                            return !se.test(k.css(t, "display")) || (t.getClientRects().length && t.getBoundingClientRect().width)
                                ? he(t, l, i)
                                : at(t, re, function () {
                                      return he(t, l, i);
                                  });
                    },
                    set: function (t, e, i) {
                        var n,
                            s = Xt(t),
                            o = !y.scrollboxSize() && "absolute" === s.position,
                            r = (o || i) && "border-box" === k.css(t, "boxSizing", !1, s),
                            a = i ? ce(t, l, i, r, s) : 0;
                        return (
                            r && o && (a -= Math.ceil(t["offset" + l[0].toUpperCase() + l.slice(1)] - parseFloat(s[l]) - ce(t, l, "border", !1, s) - 0.5)),
                            a && (n = it.exec(e)) && "px" !== (n[3] || "px") && ((t.style[l] = e), (e = k.css(t, l))),
                            le(0, e, a)
                        );
                    },
                };
            }),
            (k.cssHooks.marginLeft = Jt(y.reliableMarginLeft, function (t, e) {
                if (e)
                    return (
                        (parseFloat(Zt(t, "marginLeft")) ||
                            t.getBoundingClientRect().left -
                                at(t, { marginLeft: 0 }, function () {
                                    return t.getBoundingClientRect().left;
                                })) + "px"
                    );
            })),
            k.each({ margin: "", padding: "", border: "Width" }, function (s, o) {
                (k.cssHooks[s + o] = {
                    expand: function (t) {
                        for (var e = 0, i = {}, n = "string" === typeof t ? t.split(" ") : [t]; e < 4; e++) i[s + nt[e] + o] = n[e] || n[e - 2] || n[0];
                        return i;
                    },
                }),
                    "margin" !== s && (k.cssHooks[s + o].set = le);
            }),
            k.fn.extend({
                css: function (t, e) {
                    return W(
                        this,
                        function (t, e, i) {
                            var n,
                                s,
                                o = {},
                                r = 0;
                            if (Array.isArray(e)) {
                                for (n = Xt(t), s = e.length; r < s; r++) o[e[r]] = k.css(t, e[r], !1, n);
                                return o;
                            }
                            return void 0 !== i ? k.style(t, e, i) : k.css(t, e);
                        },
                        t,
                        e,
                        1 < arguments.length
                    );
                },
            }),
            (((k.Tween = ue).prototype = {
                constructor: ue,
                init: function (t, e, i, n, s, o) {
                    (this.elem = t), (this.prop = i), (this.easing = s || k.easing._default), (this.options = e), (this.start = this.now = this.cur()), (this.end = n), (this.unit = o || (k.cssNumber[i] ? "" : "px"));
                },
                cur: function () {
                    var t = ue.propHooks[this.prop];
                    return t && t.get ? t.get(this) : ue.propHooks._default.get(this);
                },
                run: function (t) {
                    var e,
                        i = ue.propHooks[this.prop];
                    return (
                        this.options.duration ? (this.pos = e = k.easing[this.easing](t, this.options.duration * t, 0, 1, this.options.duration)) : (this.pos = e = t),
                        (this.now = (this.end - this.start) * e + this.start),
                        this.options.step && this.options.step.call(this.elem, this.now, this),
                        i && i.set ? i.set(this) : ue.propHooks._default.set(this),
                        this
                    );
                },
            }).init.prototype = ue.prototype),
            ((ue.propHooks = {
                _default: {
                    get: function (t) {
                        var e;
                        return 1 !== t.elem.nodeType || (null !==    t.elem[t.prop] && null === t.elem.style[t.prop]) ? t.elem[t.prop] : (e = k.css(t.elem, t.prop, "")) && "auto" !== e ? e : 0;
                    },
                    set: function (t) {
                        k.fx.step[t.prop] ? k.fx.step[t.prop](t) : 1 !== t.elem.nodeType || (!k.cssHooks[t.prop] && null === t.elem.style[ne(t.prop)]) ? (t.elem[t.prop] = t.now) : k.style(t.elem, t.prop, t.now + t.unit);
                    },
                },
            }).scrollTop = ue.propHooks.scrollLeft = {
                set: function (t) {
                    t.elem.nodeType && t.elem.parentNode && (t.elem[t.prop] = t.now);
                },
            }),
            (k.easing = {
                linear: function (t) {
                    return t;
                },
                swing: function (t) {
                    return 0.5 - Math.cos(t * Math.PI) / 2;
                },
                _default: "swing",
            }),
            (k.fx = ue.prototype.init),
            (k.fx.step = {});
        var de,
            pe,
            fe,
            ge,
            me = /^(?:toggle|show|hide)$/,
            ve = /queueHooks$/;
        function ye() {
            pe && (!1 === D.hidden && C.requestAnimationFrame ? C.requestAnimationFrame(ye) : C.setTimeout(ye, k.fx.interval), k.fx.tick());
        }
        function be() {
            return (
                C.setTimeout(function () {
                    de = void 0;
                }),
                (de = Date.now())
            );
        }
        function we(t, e) {
            var i,
                n = 0,
                s = { height: t };
            for (e = e ? 1 : 0; n < 4; n += 2 - e) s["margin" + (i = nt[n])] = s["padding" + i] = t;
            return e && (s.opacity = s.width = t), s;
        }
        function _e(t, e, i) {
            for (var n, s = (xe.tweeners[e] || []).concat(xe.tweeners["*"]), o = 0, r = s.length; o < r; o++) if ((n = s[o].call(i, e, t))) return n;
        }
        function xe(o, t, e) {
            var i,
                r,
                n = 0,
                s = xe.prefilters.length,
                a = k.Deferred().always(function () {
                    delete l.elem;
                }),
                l = function () {
                    if (r) return !1;
                    for (var t = de || be(), e = Math.max(0, c.startTime + c.duration - t), i = 1 - (e / c.duration || 0), n = 0, s = c.tweens.length; n < s; n++) c.tweens[n].run(i);
                    return a.notifyWith(o, [c, i, e]), i < 1 && s ? e : (s || a.notifyWith(o, [c, 1, 0]), a.resolveWith(o, [c]), !1);
                },
                c = a.promise({
                    elem: o,
                    props: k.extend({}, t),
                    opts: k.extend(!0, { specialEasing: {}, easing: k.easing._default }, e),
                    originalProperties: t,
                    originalOptions: e,
                    startTime: de || be(),
                    duration: e.duration,
                    tweens: [],
                    createTween: function (t, e) {
                        var i = k.Tween(o, c.opts, t, e, c.opts.specialEasing[t] || c.opts.easing);
                        return c.tweens.push(i), i;
                    },
                    stop: function (t) {
                        var e = 0,
                            i = t ? c.tweens.length : 0;
                        if (r) return this;
                        for (r = !0; e < i; e++) c.tweens[e].run(1);
                        return t ? (a.notifyWith(o, [c, 1, 0]), a.resolveWith(o, [c, t])) : a.rejectWith(o, [c, t]), this;
                    },
                }),
                h = c.props;
            for (
                !(function (t, e) {
                    var i, n, s, o, r;
                    for (i in t)
                        if (((s = e[(n = V(i))]), (o = t[i]), Array.isArray(o) && ((s = o[1]), (o = t[i] = o[0])), i !== n && ((t[n] = o), delete t[i]), (r = k.cssHooks[n]) && ("expand" in r)))
                            for (i in ((o = r.expand(o)), delete t[n], o)) (i in t) || ((t[i] = o[i]), (e[i] = s));
                        else e[n] = s;
                })(h, c.opts.specialEasing);
                n < s;
                n++
            )
                if ((i = xe.prefilters[n].call(c, o, h, c.opts))) return b(i.stop) && (k._queueHooks(c.elem, c.opts.queue).stop = i.stop.bind(i)), i;
            return (
                k.map(h, _e, c),
                b(c.opts.start) && c.opts.start.call(o, c),
                c.progress(c.opts.progress).done(c.opts.done, c.opts.complete).fail(c.opts.fail).always(c.opts.always),
                k.fx.timer(k.extend(l, { elem: o, anim: c, queue: c.opts.queue })),
                c
            );
        }
        (k.Animation = k.extend(xe, {
            tweeners: {
                "*": [
                    function (t, e) {
                        var i = this.createTween(t, e);
                        return ct(i.elem, t, it.exec(e), i), i;
                    },
                ],
            },
            tweener: function (t, e) {
                for (var i, n = 0, s = (t = b(t) ? ((e = t), ["*"]) : t.match(M)).length; n < s; n++) (i = t[n]), (xe.tweeners[i] = xe.tweeners[i] || []), xe.tweeners[i].unshift(e);
            },
            prefilters: [
                function (t, e, i) {
                    var n,
                        s,
                        o,
                        r,
                        a,
                        l,
                        c,
                        h,
                        u = "width" in e || "height" in e,
                        d = this,
                        p = {},
                        f = t.style,
                        g = t.nodeType && lt(t),
                        m = Q.get(t, "fxshow");
                    for (n in (i.queue ||
                        (null === (r = k._queueHooks(t, "fx")).unqueued &&
                            ((r.unqueued = 0),
                            (a = r.empty.fire),
                            (r.empty.fire = function () {
                                r.unqueued || a();
                            })),
                        r.unqueued++,
                        d.always(function () {
                            d.always(function () {
                                r.unqueued--, k.queue(t, "fx").length || r.empty.fire();
                            });
                        })),
                    e))
                        if (((s = e[n]), me.test(s))) {
                            if ((delete e[n], (o = o || "toggle" === s), s === (g ? "hide" : "show"))) {
                                if ("show" !== s || !m || void 0 === m[n]) continue;
                                g = !0;
                            }
                            p[n] = (m && m[n]) || k.style(t, n);
                        }
                    if ((l = !k.isEmptyObject(e)) || !k.isEmptyObject(p))
                        for (n in (u &&
                            1 === t.nodeType &&
                            ((i.overflow = [f.overflow, f.overflowX, f.overflowY]),
                            null === (c = m && m.display) && (c = Q.get(t, "display")),
                            "none" === (h = k.css(t, "display")) && (c ? (h = c) : (ut([t], !0), (c = t.style.display || c), (h = k.css(t, "display")), ut([t]))),
                            ("inline" === h || ("inline-block" === h && null !==    c)) &&
                                "none" === k.css(t, "float") &&
                                (l ||
                                    (d.done(function () {
                                        f.display = c;
                                    }),
                                    null === c && ((h = f.display), (c = "none" === h ? "" : h))),
                                (f.display = "inline-block"))),
                        i.overflow &&
                            ((f.overflow = "hidden"),
                            d.always(function () {
                                (f.overflow = i.overflow[0]), (f.overflowX = i.overflow[1]), (f.overflowY = i.overflow[2]);
                            })),
                        (l = !1),
                        p))
                            l ||
                                (m ? "hidden" in m && (g = m.hidden) : (m = Q.access(t, "fxshow", { display: c })),
                                o && (m.hidden = !g),
                                g && ut([t], !0),
                                d.done(function () {
                                    for (n in (g || ut([t]), Q.remove(t, "fxshow"), p)) k.style(t, n, p[n]);
                                })),
                                (l = _e(g ? m[n] : 0, n, d)),
                                n in m || ((m[n] = l.start), g && ((l.end = l.start), (l.start = 0)));
                },
            ],
            prefilter: function (t, e) {
                e ? xe.prefilters.unshift(t) : xe.prefilters.push(t);
            },
        })),
            (k.speed = function (t, e, i) {
                var n = t && "object" === typeof t ? k.extend({}, t) : { complete: i || (!i && e) || (b(t) && t), duration: t, easing: (i && e) || (e && !b(e) && e) };
                return (
                    k.fx.off ? (n.duration = 0) : "number" !==    typeof n.duration && (n.duration in k.fx.speeds ? (n.duration = k.fx.speeds[n.duration]) : (n.duration = k.fx.speeds._default)),
                    (null !==    n.queue && !0 !== n.queue) || (n.queue = "fx"),
                    (n.old = n.complete),
                    (n.complete = function () {
                        b(n.old) && n.old.call(this), n.queue && k.dequeue(this, n.queue);
                    }),
                    n
                );
            }),
            k.fn.extend({
                fadeTo: function (t, e, i, n) {
                    return this.filter(lt).css("opacity", 0).show().end().animate({ opacity: e }, t, i, n);
                },
                animate: function (e, t, i, n) {
                    function s() {
                        var t = xe(this, k.extend({}, e), r);
                        (o || Q.get(this, "finish")) && t.stop(!0);
                    }
                    var o = k.isEmptyObject(e),
                        r = k.speed(t, i, n);
                    return (s.finish = s), o || !1 === r.queue ? this.each(s) : this.queue(r.queue, s);
                },
                stop: function (s, t, o) {
                    function r(t) {
                        var e = t.stop;
                        delete t.stop, e(o);
                    }
                    return (
                        "string" !==    typeof s && ((o = t), (t = s), (s = void 0)),
                        t && !1 !== s && this.queue(s || "fx", []),
                        this.each(function () {
                            var t = !0,
                                e = null !==    s && s + "queueHooks",
                                i = k.timers,
                                n = Q.get(this);
                            if (e) n[e] && n[e].stop && r(n[e]);
                            else for (e in n) n[e] && n[e].stop && ve.test(e) && r(n[e]);
                            for (e = i.length; e--; ) i[e].elem !== this || (null !==    s && i[e].queue !== s) || (i[e].anim.stop(o), (t = !1), i.splice(e, 1));
                            (!t && o) || k.dequeue(this, s);
                        })
                    );
                },
                finish: function (r) {
                    return (
                        !1 !== r && (r = r || "fx"),
                        this.each(function () {
                            var t,
                                e = Q.get(this),
                                i = e[r + "queue"],
                                n = e[r + "queueHooks"],
                                s = k.timers,
                                o = i ? i.length : 0;
                            for (e.finish = !0, k.queue(this, r, []), n && n.stop && n.stop.call(this, !0), t = s.length; t--; ) s[t].elem === this && s[t].queue === r && (s[t].anim.stop(!0), s.splice(t, 1));
                            for (t = 0; t < o; t++) i[t] && i[t].finish && i[t].finish.call(this);
                            delete e.finish;
                        })
                    );
                },
            }),
            k.each(["toggle", "show", "hide"], function (t, n) {
                var s = k.fn[n];
                k.fn[n] = function (t, e, i) {
                    return null === t || "boolean" === typeof t ? s.apply(this, arguments) : this.animate(we(n, !0), t, e, i);
                };
            }),
            k.each({ slideDown: we("show"), slideUp: we("hide"), slideToggle: we("toggle"), fadeIn: { opacity: "show" }, fadeOut: { opacity: "hide" }, fadeToggle: { opacity: "toggle" } }, function (t, n) {
                k.fn[t] = function (t, e, i) {
                    return this.animate(n, t, e, i);
                };
            }),
            (k.timers = []),
            (k.fx.tick = function () {
                var t,
                    e = 0,
                    i = k.timers;
                for (de = Date.now(); e < i.length; e++) (t = i[e])() || i[e] !== t || i.splice(e--, 1);
                i.length || k.fx.stop(), (de = void 0);
            }),
            (k.fx.timer = function (t) {
                k.timers.push(t), k.fx.start();
            }),
            (k.fx.interval = 13),
            (k.fx.start = function () {
                pe || ((pe = !0), ye());
            }),
            (k.fx.stop = function () {
                pe = null;
            }),
            (k.fx.speeds = { slow: 600, fast: 200, _default: 400 }),
            (k.fn.delay = function (n, t) {
                return (
                    (n = (k.fx && k.fx.speeds[n]) || n),
                    (t = t || "fx"),
                    this.queue(t, function (t, e) {
                        var i = C.setTimeout(t, n);
                        e.stop = function () {
                            C.clearTimeout(i);
                        };
                    })
                );
            }),
            (fe = D.createElement("input")),
            (ge = D.createElement("select").appendChild(D.createElement("option"))),
            (fe.type = "checkbox"),
            (y.checkOn = "" !== fe.value),
            (y.optSelected = ge.selected),
            ((fe = D.createElement("input")).value = "t"),
            (fe.type = "radio"),
            (y.radioValue = "t" === fe.value);
        var Ce,
            De = k.expr.attrHandle;
        k.fn.extend({
            attr: function (t, e) {
                return W(this, k.attr, t, e, 1 < arguments.length);
            },
            removeAttr: function (t) {
                return this.each(function () {
                    k.removeAttr(this, t);
                });
            },
        }),
            k.extend({
                attr: function (t, e, i) {
                    var n,
                        s,
                        o = t.nodeType;
                    if (3 !== o && 8 !== o && 2 !== o)
                        return void 0 === t.getAttribute
                            ? k.prop(t, e, i)
                            : ((1 === o && k.isXMLDoc(t)) || (s = k.attrHooks[e.toLowerCase()] || (k.expr.match.bool.test(e) ? Ce : void 0)),
                              void 0 !== i
                                  ? null === i
                                      ? void k.removeAttr(t, e)
                                      : s && "set" in s && void 0 !== (n = s.set(t, i, e))
                                      ? n
                                      : (t.setAttribute(e, i + ""), i)
                                  : !(s && "get" in s && null !== (n = s.get(t, e))) && null === (n = k.find.attr(t, e))
                                  ? void 0
                                  : n);
                },
                attrHooks: {
                    type: {
                        set: function (t, e) {
                            if (!y.radioValue && "radio" === e && E(t, "input")) {
                                var i = t.value;
                                return t.setAttribute("type", e), i && (t.value = i), e;
                            }
                        },
                    },
                },
                removeAttr: function (t, e) {
                    var i,
                        n = 0,
                        s = e && e.match(M);
                    if (s && 1 === t.nodeType) for (; (i = s[n++]); ) t.removeAttribute(i);
                },
            }),
            (Ce = {
                set: function (t, e, i) {
                    return !1 === e ? k.removeAttr(t, i) : t.setAttribute(i, i), i;
                },
            }),
            k.each(k.expr.match.bool.source.match(/\w+/g), function (t, e) {
                var r = De[e] || k.find.attr;
                De[e] = function (t, e, i) {
                    var n,
                        s,
                        o = e.toLowerCase();
                    return i || ((s = De[o]), (De[o] = n), (n = null !==    r(t, e, i) ? o : null), (De[o] = s)), n;
                };
            });
        var ke = /^(?:input|select|textarea|button)$/i,
            Te = /^(?:a|area)$/i;
        function Se(t) {
            return (t.match(M) || []).join(" ");
        }
        function Ee(t) {
            return (t.getAttribute && t.getAttribute("class")) || "";
        }
        function Ae(t) {
            return Array.isArray(t) ? t : ("string" === typeof t && t.match(M)) || [];
        }
        k.fn.extend({
            prop: function (t, e) {
                return W(this, k.prop, t, e, 1 < arguments.length);
            },
            removeProp: function (t) {
                return this.each(function () {
                    delete this[k.propFix[t] || t];
                });
            },
        }),
            k.extend({
                prop: function (t, e, i) {
                    var n,
                        s,
                        o = t.nodeType;
                    if (3 !== o && 8 !== o && 2 !== o)
                        return (
                            (1 === o && k.isXMLDoc(t)) || ((e = k.propFix[e] || e), (s = k.propHooks[e])),
                            void 0 !== i ? (s && "set" in s && void 0 !== (n = s.set(t, i, e)) ? n : (t[e] = i)) : s && "get" in s && null !== (n = s.get(t, e)) ? n : t[e]
                        );
                },
                propHooks: {
                    tabIndex: {
                        get: function (t) {
                            var e = k.find.attr(t, "tabindex");
                            return e ? parseInt(e, 10) : ke.test(t.nodeName) || (Te.test(t.nodeName) && t.href) ? 0 : -1;
                        },
                    },
                },
                propFix: { for: "htmlFor", class: "className" },
            }),
            y.optSelected ||
                (k.propHooks.selected = {
                    get: function (t) {
                        var e = t.parentNode;
                        return e && e.parentNode && e.parentNode.selectedIndex, null;
                    },
                    set: function (t) {
                        var e = t.parentNode;
                        e && (e.selectedIndex, e.parentNode && e.parentNode.selectedIndex);
                    },
                }),
            k.each(["tabIndex", "readOnly", "maxLength", "cellSpacing", "cellPadding", "rowSpan", "colSpan", "useMap", "frameBorder", "contentEditable"], function () {
                k.propFix[this.toLowerCase()] = this;
            }),
            k.fn.extend({
                addClass: function (e) {
                    var t,
                        i,
                        n,
                        s,
                        o,
                        r,
                        a,
                        l = 0;
                    if (b(e))
                        return this.each(function (t) {
                            k(this).addClass(e.call(this, t, Ee(this)));
                        });
                    if ((t = Ae(e)).length)
                        for (; (i = this[l++]); )
                            if (((s = Ee(i)), (n = 1 === i.nodeType && " " + Se(s) + " "))) {
                                for (r = 0; (o = t[r++]); ) n.indexOf(" " + o + " ") < 0 && (n += o + " ");
                                s !== (a = Se(n)) && i.setAttribute("class", a);
                            }
                    return this;
                },
                removeClass: function (e) {
                    var t,
                        i,
                        n,
                        s,
                        o,
                        r,
                        a,
                        l = 0;
                    if (b(e))
                        return this.each(function (t) {
                            k(this).removeClass(e.call(this, t, Ee(this)));
                        });
                    if (!arguments.length) return this.attr("class", "");
                    if ((t = Ae(e)).length)
                        for (; (i = this[l++]); )
                            if (((s = Ee(i)), (n = 1 === i.nodeType && " " + Se(s) + " "))) {
                                for (r = 0; (o = t[r++]); ) for (; -1 < n.indexOf(" " + o + " "); ) n = n.replace(" " + o + " ", " ");
                                s !== (a = Se(n)) && i.setAttribute("class", a);
                            }
                    return this;
                },
                toggleClass: function (s, e) {
                    var o = typeof s,
                        r = "string" === o || Array.isArray(s);
                    return "boolean" === typeof e && r
                        ? e
                            ? this.addClass(s)
                            : this.removeClass(s)
                        : b(s)
                        ? this.each(function (t) {
                              k(this).toggleClass(s.call(this, t, Ee(this), e), e);
                          })
                        : this.each(function () {
                              var t, e, i, n;
                              if (r) for (e = 0, i = k(this), n = Ae(s); (t = n[e++]); ) i.hasClass(t) ? i.removeClass(t) : i.addClass(t);
                              else (void 0 !== s && "boolean" !==    o) || ((t = Ee(this)) && Q.set(this, "__className__", t), this.setAttribute && this.setAttribute("class", (!t && !1 !== s && Q.get(this, "__className__")) || ""));
                          });
                },
                hasClass: function (t) {
                    var e,
                        i,
                        n = 0;
                    for (e = " " + t + " "; (i = this[n++]); ) if (1 === i.nodeType && -1 < (" " + Se(Ee(i)) + " ").indexOf(e)) return !0;
                    return !1;
                },
            });
        var Pe = /\r/g;
        k.fn.extend({
            val: function (i) {
                var n,
                    t,
                    s,
                    e = this[0];
                return arguments.length
                    ? ((s = b(i)),
                      this.each(function (t) {
                          var e;
                          1 === this.nodeType &&
                              (null === (e = s ? i.call(this, t, k(this).val()) : i)
                                  ? (e = "")
                                  : "number" === typeof e
                                  ? (e += "")
                                  : Array.isArray(e) &&
                                    (e = k.map(e, function (t) {
                                        return null === t ? "" : t + "";
                                    })),
                              ((n = k.valHooks[this.type] || k.valHooks[this.nodeName.toLowerCase()]) && "set" in n && void 0 !== n.set(this, e, "value")) || (this.value = e));
                      }))
                    : e
                    ? (n = k.valHooks[e.type] || k.valHooks[e.nodeName.toLowerCase()]) && "get" in n && void 0 !== (t = n.get(e, "value"))
                        ? t
                        : "string" === typeof (t = e.value)
                        ? t.replace(Pe, "")
                        : null === t
                        ? ""
                        : t
                    : void 0;
            },
        }),
            k.extend({
                valHooks: {
                    option: {
                        get: function (t) {
                            var e = k.find.attr(t, "value");
                            return null !==    e ? e : Se(k.text(t));
                        },
                    },
                    select: {
                        get: function (t) {
                            var e,
                                i,
                                n,
                                s = t.options,
                                o = t.selectedIndex,
                                r = "select-one" === t.type,
                                a = r ? null : [],
                                l = r ? o + 1 : s.length;
                            for (n = o < 0 ? l : r ? o : 0; n < l; n++)
                                if (((i = s[n]).selected || n === o) && !i.disabled && (!i.parentNode.disabled || !E(i.parentNode, "optgroup"))) {
                                    if (((e = k(i).val()), r)) return e;
                                    a.push(e);
                                }
                            return a;
                        },
                        set: function (t, e) {
                            for (var i, n, s = t.options, o = k.makeArray(e), r = s.length; r--; ) ((n = s[r]).selected = -1 < k.inArray(k.valHooks.option.get(n), o)) && (i = !0);
                            return i || (t.selectedIndex = -1), o;
                        },
                    },
                },
            }),
            k.each(["radio", "checkbox"], function () {
                (k.valHooks[this] = {
                    set: function (t, e) {
                        if (Array.isArray(e)) return (t.checked = -1 < k.inArray(k(t).val(), e));
                    },
                }),
                    y.checkOn ||
                        (k.valHooks[this].get = function (t) {
                            return null === t.getAttribute("value") ? "on" : t.value;
                        });
            }),
            (y.focusin = "onfocusin" in C);
        function $e(t) {
            t.stopPropagation();
        }
        var Ie = /^(?:focusinfocus|focusoutblur)$/;
        k.extend(k.event, {
            trigger: function (t, e, i, n) {
                var s,
                    o,
                    r,
                    a,
                    l,
                    c,
                    h,
                    u,
                    d = [i || D],
                    p = v.call(t, "type") ? t.type : t,
                    f = v.call(t, "namespace") ? t.namespace.split(".") : [];
                if (
                    ((o = u = r = i = i || D),
                    3 !== i.nodeType &&
                        8 !== i.nodeType &&
                        !Ie.test(p + k.event.triggered) &&
                        (-1 < p.indexOf(".") && ((p = (f = p.split(".")).shift()), f.sort()),
                        (l = p.indexOf(":") < 0 && "on" + p),
                        ((t = t[k.expando] ? t : new k.Event(p, "object" === typeof t && t)).isTrigger = n ? 2 : 3),
                        (t.namespace = f.join(".")),
                        (t.rnamespace = t.namespace ? new RegExp("(^|\\.)" + f.join("\\.(?:.*\\.|)") + "(\\.|$)") : null),
                        (t.result = void 0),
                        t.target || (t.target = i),
                        (e = null === e ? [t] : k.makeArray(e, [t])),
                        (h = k.event.special[p] || {}),
                        n || !h.trigger || !1 !== h.trigger.apply(i, e)))
                ) {
                    if (!n && !h.noBubble && !g(i)) {
                        for (a = h.delegateType || p, Ie.test(a + p) || (o = o.parentNode); o; o = o.parentNode) d.push(o), (r = o);
                        r === (i.ownerDocument || D) && d.push(r.defaultView || r.parentWindow || C);
                    }
                    for (s = 0; (o = d[s++]) && !t.isPropagationStopped(); )
                        (u = o),
                            (t.type = 1 < s ? a : h.bindType || p),
                            (c = (Q.get(o, "events") || {})[t.type] && Q.get(o, "handle")) && c.apply(o, e),
                            (c = l && o[l]) && c.apply && X(o) && ((t.result = c.apply(o, e)), !1 === t.result && t.preventDefault());
                    return (
                        (t.type = p),
                        n ||
                            t.isDefaultPrevented() ||
                            (h._default && !1 !== h._default.apply(d.pop(), e)) ||
                            !X(i) ||
                            (l &&
                                b(i[p]) &&
                                !g(i) &&
                                ((r = i[l]) && (i[l] = null),
                                (k.event.triggered = p),
                                t.isPropagationStopped() && u.addEventListener(p, $e),
                                i[p](),
                                t.isPropagationStopped() && u.removeEventListener(p, $e),
                                (k.event.triggered = void 0),
                                r && (i[l] = r))),
                        t.result
                    );
                }
            },
            simulate: function (t, e, i) {
                var n = k.extend(new k.Event(), i, { type: t, isSimulated: !0 });
                k.event.trigger(n, null, e);
            },
        }),
            k.fn.extend({
                trigger: function (t, e) {
                    return this.each(function () {
                        k.event.trigger(t, e, this);
                    });
                },
                triggerHandler: function (t, e) {
                    var i = this[0];
                    if (i) return k.event.trigger(t, e, i, !0);
                },
            }),
            y.focusin ||
                k.each({ focus: "focusin", blur: "focusout" }, function (i, n) {
                    function s(t) {
                        k.event.simulate(n, t.target, k.event.fix(t));
                    }
                    k.event.special[n] = {
                        setup: function () {
                            var t = this.ownerDocument || this,
                                e = Q.access(t, n);
                            e || t.addEventListener(i, s, !0), Q.access(t, n, (e || 0) + 1);
                        },
                        teardown: function () {
                            var t = this.ownerDocument || this,
                                e = Q.access(t, n) - 1;
                            e ? Q.access(t, n, e) : (t.removeEventListener(i, s, !0), Q.remove(t, n));
                        },
                    };
                });
        var ze = C.location,
            Oe = Date.now(),
            Ne = /\?/;
        k.parseXML = function (t) {
            var e;
            if (!t || "string" !==    typeof t) return null;
            try {
                e = new C.DOMParser().parseFromString(t, "text/xml");
            } catch (t) {
                e = void 0;
            }
            return (e && !e.getElementsByTagName("parsererror").length) || k.error("Invalid XML: " + t), e;
        };
        var Me = /\[\]$/,
            Fe = /\r?\n/g,
            Le = /^(?:submit|button|image|reset|file)$/i,
            He = /^(?:input|select|textarea|keygen)/i;
        function Re(i, t, n, s) {
            var e;
            if (Array.isArray(t))
                k.each(t, function (t, e) {
                    n || Me.test(i) ? s(i, e) : Re(i + "[" + ("object" === typeof e && null !==    e ? t : "") + "]", e, n, s);
                });
            else if (n || "object" !== _(t)) s(i, t);
            else for (e in t) Re(i + "[" + e + "]", t[e], n, s);
        }
        (k.param = function (t, e) {
            function i(t, e) {
                var i = b(e) ? e() : e;
                s[s.length] = encodeURIComponent(t) + "=" + encodeURIComponent(null === i ? "" : i);
            }
            var n,
                s = [];
            if (null === t) return "";
            if (Array.isArray(t) || (t.jquery && !k.isPlainObject(t)))
                k.each(t, function () {
                    i(this.name, this.value);
                });
            else for (n in t) Re(n, t[n], e, i);
            return s.join("&");
        }),
            k.fn.extend({
                serialize: function () {
                    return k.param(this.serializeArray());
                },
                serializeArray: function () {
                    return this.map(function () {
                        var t = k.prop(this, "elements");
                        return t ? k.makeArray(t) : this;
                    })
                        .filter(function () {
                            var t = this.type;
                            return this.name && !k(this).is(":disabled") && He.test(this.nodeName) && !Le.test(t) && (this.checked || !dt.test(t));
                        })
                        .map(function (t, e) {
                            var i = k(this).val();
                            return null === i
                                ? null
                                : Array.isArray(i)
                                ? k.map(i, function (t) {
                                      return { name: e.name, value: t.replace(Fe, "\r\n") };
                                  })
                                : { name: e.name, value: i.replace(Fe, "\r\n") };
                        })
                        .get();
                },
            });
        var je = /%20/g,
            Ue = /#.*$/,
            We = /([?&])_=[^&]*/,
            qe = /^(.*?):[ \t]*([^\r\n]*)$/gm,
            Be = /^(?:GET|HEAD)$/,
            Ye = /^\/\//,
            Ve = {},
            Xe = {},
            Ge = "*/".concat("*"),
            Qe = D.createElement("a");
        function Ke(o) {
            return function (t, e) {
                "string" !==    typeof t && ((e = t), (t = "*"));
                var i,
                    n = 0,
                    s = t.toLowerCase().match(M) || [];
                if (b(e)) for (; (i = s[n++]); ) "+" === i[0] ? ((i = i.slice(1) || "*"), (o[i] = o[i] || []).unshift(e)) : (o[i] = o[i] || []).push(e);
            };
        }
        function Ze(e, s, o, r) {
            var a = {},
                l = e === Xe;
            function c(t) {
                var n;
                return (
                    (a[t] = !0),
                    k.each(e[t] || [], function (t, e) {
                        var i = e(s, o, r);
                        return "string" !==    typeof i || l || a[i] ? (l ? !(n = i) : void 0) : (s.dataTypes.unshift(i), c(i), !1);
                    }),
                    n
                );
            }
            return c(s.dataTypes[0]) || (!a["*"] && c("*"));
        }
        function Je(t, e) {
            var i,
                n,
                s = k.ajaxSettings.flatOptions || {};
            for (i in e) void 0 !== e[i] && ((s[i] ? t : (n = n || {}))[i] = e[i]);
            return n && k.extend(!0, t, n), t;
        }
        (Qe.href = ze.href),
            k.extend({
                active: 0,
                lastModified: {},
                etag: {},
                ajaxSettings: {
                    url: ze.href,
                    type: "GET",
                    isLocal: /^(?:about|app|app-storage|.+-extension|file|res|widget):$/.test(ze.protocol),
                    global: !0,
                    processData: !0,
                    async: !0,
                    contentType: "application/x-www-form-urlencoded; charset=UTF-8",
                    accepts: { "*": Ge, text: "text/plain", html: "text/html", xml: "application/xml, text/xml", json: "application/json, text/javascript" },
                    contents: { xml: /\bxml\b/, html: /\bhtml/, json: /\bjson\b/ },
                    responseFields: { xml: "responseXML", text: "responseText", json: "responseJSON" },
                    converters: { "* text": String, "text html": !0, "text json": JSON.parse, "text xml": k.parseXML },
                    flatOptions: { url: !0, context: !0 },
                },
                ajaxSetup: function (t, e) {
                    return e ? Je(Je(t, k.ajaxSettings), e) : Je(k.ajaxSettings, t);
                },
                ajaxPrefilter: Ke(Ve),
                ajaxTransport: Ke(Xe),
                ajax: function (t, e) {
                    "object" === typeof t && ((e = t), (t = void 0)), (e = e || {});
                    var h,
                        u,
                        d,
                        i,
                        p,
                        n,
                        f,
                        g,
                        s,
                        o,
                        m = k.ajaxSetup({}, e),
                        v = m.context || m,
                        y = m.context && (v.nodeType || v.jquery) ? k(v) : k.event,
                        b = k.Deferred(),
                        w = k.Callbacks("once memory"),
                        _ = m.statusCode || {},
                        r = {},
                        a = {},
                        l = "canceled",
                        x = {
                            readyState: 0,
                            getResponseHeader: function (t) {
                                var e;
                                if (f) {
                                    if (!i) for (i = {}; (e = qe.exec(d)); ) i[e[1].toLowerCase() + " "] = (i[e[1].toLowerCase() + " "] || []).concat(e[2]);
                                    e = i[t.toLowerCase() + " "];
                                }
                                return null === e ? null : e.join(", ");
                            },
                            getAllResponseHeaders: function () {
                                return f ? d : null;
                            },
                            setRequestHeader: function (t, e) {
                                return null === f && ((t = a[t.toLowerCase()] = a[t.toLowerCase()] || t), (r[t] = e)), this;
                            },
                            overrideMimeType: function (t) {
                                return null === f && (m.mimeType = t), this;
                            },
                            statusCode: function (t) {
                                var e;
                                if (t)
                                    if (f) x.always(t[x.status]);
                                    else for (e in t) _[e] = [_[e], t[e]];
                                return this;
                            },
                            abort: function (t) {
                                var e = t || l;
                                return h && h.abort(e), c(0, e), this;
                            },
                        };
                    if (
                        (b.promise(x),
                        (m.url = ((t || m.url || ze.href) + "").replace(Ye, ze.protocol + "//")),
                        (m.type = e.method || e.type || m.method || m.type),
                        (m.dataTypes = (m.dataType || "*").toLowerCase().match(M) || [""]),
                        null === m.crossDomain)
                    ) {
                        n = D.createElement("a");
                        try {
                            (n.href = m.url), (n.href = n.href), (m.crossDomain = Qe.protocol + "//" + Qe.host !==    n.protocol + "//" + n.host);
                        } catch (t) {
                            m.crossDomain = !0;
                        }
                    }
                    if ((m.data && m.processData && "string" !==    typeof m.data && (m.data = k.param(m.data, m.traditional)), Ze(Ve, m, e, x), f)) return x;
                    for (s in ((g = k.event && m.global) && 0 === k.active++ && k.event.trigger("ajaxStart"),
                    (m.type = m.type.toUpperCase()),
                    (m.hasContent = !Be.test(m.type)),
                    (u = m.url.replace(Ue, "")),
                    m.hasContent
                        ? m.data && m.processData && 0 === (m.contentType || "").indexOf("application/x-www-form-urlencoded") && (m.data = m.data.replace(je, "+"))
                        : ((o = m.url.slice(u.length)),
                          m.data && (m.processData || "string" === typeof m.data) && ((u += (Ne.test(u) ? "&" : "?") + m.data), delete m.data),
                          !1 === m.cache && ((u = u.replace(We, "$1")), (o = (Ne.test(u) ? "&" : "?") + "_=" + Oe++ + o)),
                          (m.url = u + o)),
                    m.ifModified && (k.lastModified[u] && x.setRequestHeader("If-Modified-Since", k.lastModified[u]), k.etag[u] && x.setRequestHeader("If-None-Match", k.etag[u])),
                    ((m.data && m.hasContent && !1 !== m.contentType) || e.contentType) && x.setRequestHeader("Content-Type", m.contentType),
                    x.setRequestHeader("Accept", m.dataTypes[0] && m.accepts[m.dataTypes[0]] ? m.accepts[m.dataTypes[0]] + ("*" !== m.dataTypes[0] ? ", " + Ge + "; q=0.01" : "") : m.accepts["*"]),
                    m.headers))
                        x.setRequestHeader(s, m.headers[s]);
                    if (m.beforeSend && (!1 === m.beforeSend.call(v, x, m) || f)) return x.abort();
                    if (((l = "abort"), w.add(m.complete), x.done(m.success), x.fail(m.error), (h = Ze(Xe, m, e, x)))) {
                        if (((x.readyState = 1), g && y.trigger("ajaxSend", [x, m]), f)) return x;
                        m.async &&
                            0 < m.timeout &&
                            (p = C.setTimeout(function () {
                                x.abort("timeout");
                            }, m.timeout));
                        try {
                            (f = !1), h.send(r, c);
                        } catch (t) {
                            if (f) throw t;
                            c(-1, t);
                        }
                    } else c(-1, "No Transport");
                    function c(t, e, i, n) {
                        var s,
                            o,
                            r,
                            a,
                            l,
                            c = e;
                        f ||
                            ((f = !0),
                            p && C.clearTimeout(p),
                            (h = void 0),
                            (d = n || ""),
                            (x.readyState = 0 < t ? 4 : 0),
                            (s = (200 <= t && t < 300) || 304 === t),
                            i &&
                                (a = (function (t, e, i) {
                                    for (var n, s, o, r, a = t.contents, l = t.dataTypes; "*" === l[0]; ) l.shift(), void 0 === n && (n = t.mimeType || e.getResponseHeader("Content-Type"));
                                    if (n)
                                        for (s in a)
                                            if (a[s] && a[s].test(n)) {
                                                l.unshift(s);
                                                break;
                                            }
                                    if (l[0] in i) o = l[0];
                                    else {
                                        for (s in i) {
                                            if (!l[0] || t.converters[s + " " + l[0]]) {
                                                o = s;
                                                break;
                                            }
                                            r = r || s;
                                        }
                                        o = o || r;
                                    }
                                    if (o) return o !== l[0] && l.unshift(o), i[o];
                                })(m, x, i)),
                            (a = (function (t, e, i, n) {
                                var s,
                                    o,
                                    r,
                                    a,
                                    l,
                                    c = {},
                                    h = t.dataTypes.slice();
                                if (h[1]) for (r in t.converters) c[r.toLowerCase()] = t.converters[r];
                                for (o = h.shift(); o; )
                                    if ((t.responseFields[o] && (i[t.responseFields[o]] = e), !l && n && t.dataFilter && (e = t.dataFilter(e, t.dataType)), (l = o), (o = h.shift())))
                                        if ("*" === o) o = l;
                                        else if ("*" !== l && l !== o) {
                                            if (!(r = c[l + " " + o] || c["* " + o]))
                                                for (s in c)
                                                    if ((a = s.split(" "))[1] === o && (r = c[l + " " + a[0]] || c["* " + a[0]])) {
                                                        !0 === r ? (r = c[s]) : !0 !== c[s] && ((o = a[0]), h.unshift(a[1]));
                                                        break;
                                                    }
                                            if (!0 !== r)
                                                if (r && t.throws) e = r(e);
                                                else
                                                    try {
                                                        e = r(e);
                                                    } catch (t) {
                                                        return { state: "parsererror", error: r ? t : "No conversion from " + l + " to " + o };
                                                    }
                                        }
                                return { state: "success", data: e };
                            })(m, a, x, s)),
                            s
                                ? (m.ifModified && ((l = x.getResponseHeader("Last-Modified")) && (k.lastModified[u] = l), (l = x.getResponseHeader("etag")) && (k.etag[u] = l)),
                                  204 === t || "HEAD" === m.type ? (c = "nocontent") : 304 === t ? (c = "notmodified") : ((c = a.state), (o = a.data), (s = !(r = a.error))))
                                : ((r = c), (!t && c) || ((c = "error"), t < 0 && (t = 0))),
                            (x.status = t),
                            (x.statusText = (e || c) + ""),
                            s ? b.resolveWith(v, [o, c, x]) : b.rejectWith(v, [x, c, r]),
                            x.statusCode(_),
                            (_ = void 0),
                            g && y.trigger(s ? "ajaxSuccess" : "ajaxError", [x, m, s ? o : r]),
                            w.fireWith(v, [x, c]),
                            g && (y.trigger("ajaxComplete", [x, m]), --k.active || k.event.trigger("ajaxStop")));
                    }
                    return x;
                },
                getJSON: function (t, e, i) {
                    return k.get(t, e, i, "json");
                },
                getScript: function (t, e) {
                    return k.get(t, void 0, e, "script");
                },
            }),
            k.each(["get", "post"], function (t, s) {
                k[s] = function (t, e, i, n) {
                    return b(e) && ((n = n || i), (i = e), (e = void 0)), k.ajax(k.extend({ url: t, type: s, dataType: n, data: e, success: i }, k.isPlainObject(t) && t));
                };
            }),
            (k._evalUrl = function (t, e) {
                return k.ajax({
                    url: t,
                    type: "GET",
                    dataType: "script",
                    cache: !0,
                    async: !1,
                    global: !1,
                    converters: { "text script": function () {} },
                    dataFilter: function (t) {
                        k.globalEval(t, e);
                    },
                });
            }),
            k.fn.extend({
                wrapAll: function (t) {
                    var e;
                    return (
                        this[0] &&
                            (b(t) && (t = t.call(this[0])),
                            (e = k(t, this[0].ownerDocument).eq(0).clone(!0)),
                            this[0].parentNode && e.insertBefore(this[0]),
                            e
                                .map(function () {
                                    for (var t = this; t.firstElementChild; ) t = t.firstElementChild;
                                    return t;
                                })
                                .append(this)),
                        this
                    );
                },
                wrapInner: function (i) {
                    return b(i)
                        ? this.each(function (t) {
                              k(this).wrapInner(i.call(this, t));
                          })
                        : this.each(function () {
                              var t = k(this),
                                  e = t.contents();
                              e.length ? e.wrapAll(i) : t.append(i);
                          });
                },
                wrap: function (e) {
                    var i = b(e);
                    return this.each(function (t) {
                        k(this).wrapAll(i ? e.call(this, t) : e);
                    });
                },
                unwrap: function (t) {
                    return (
                        this.parent(t)
                            .not("body")
                            .each(function () {
                                k(this).replaceWith(this.childNodes);
                            }),
                        this
                    );
                },
            }),
            (k.expr.pseudos.hidden = function (t) {
                return !k.expr.pseudos.visible(t);
            }),
            (k.expr.pseudos.visible = function (t) {
                return !!(t.offsetWidth || t.offsetHeight || t.getClientRects().length);
            }),
            (k.ajaxSettings.xhr = function () {
                try {
                    return new C.XMLHttpRequest();
                } catch (t) {}
            });
        var ti = { 0: 200, 1223: 204 },
            ei = k.ajaxSettings.xhr();
        (y.cors = !!ei && "withCredentials" in ei),
            (y.ajax = ei = !!ei),
            k.ajaxTransport(function (s) {
                var o, r;
                if (y.cors || (ei && !s.crossDomain))
                    return {
                        send: function (t, e) {
                            var i,
                                n = s.xhr();
                            if ((n.open(s.type, s.url, s.async, s.username, s.password), s.xhrFields)) for (i in s.xhrFields) n[i] = s.xhrFields[i];
                            for (i in (s.mimeType && n.overrideMimeType && n.overrideMimeType(s.mimeType), s.crossDomain || t["X-Requested-With"] || (t["X-Requested-With"] = "XMLHttpRequest"), t)) n.setRequestHeader(i, t[i]);
                            (o = function (t) {
                                return function () {
                                    o &&
                                        ((o = r = n.onload = n.onerror = n.onabort = n.ontimeout = n.onreadystatechange = null),
                                        "abort" === t
                                            ? n.abort()
                                            : "error" === t
                                            ? "number" !==    typeof n.status
                                                ? e(0, "error")
                                                : e(n.status, n.statusText)
                                            : e(
                                                  ti[n.status] || n.status,
                                                  n.statusText,
                                                  "text" !== (n.responseType || "text") || "string" !==    typeof n.responseText ? { binary: n.response } : { text: n.responseText },
                                                  n.getAllResponseHeaders()
                                              ));
                                };
                            }),
                                (n.onload = o()),
                                (r = n.onerror = n.ontimeout = o("error")),
                                void 0 !== n.onabort
                                    ? (n.onabort = r)
                                    : (n.onreadystatechange = function () {
                                          4 === n.readyState &&
                                              C.setTimeout(function () {
                                                  o && r();
                                              });
                                      }),
                                (o = o("abort"));
                            try {
                                n.send((s.hasContent && s.data) || null);
                            } catch (t) {
                                if (o) throw t;
                            }
                        },
                        abort: function () {
                            o && o();
                        },
                    };
            }),
            k.ajaxPrefilter(function (t) {
                t.crossDomain && (t.contents.script = !1);
            }),
            k.ajaxSetup({
                accepts: { script: "text/javascript, application/javascript, application/ecmascript, application/x-ecmascript" },
                contents: { script: /\b(?:java|ecma)script\b/ },
                converters: {
                    "text script": function (t) {
                        return k.globalEval(t), t;
                    },
                },
            }),
            k.ajaxPrefilter("script", function (t) {
                void 0 === t.cache && (t.cache = !1), t.crossDomain && (t.type = "GET");
            }),
            k.ajaxTransport("script", function (i) {
                var n, s;
                if (i.crossDomain || i.scriptAttrs)
                    return {
                        send: function (t, e) {
                            (n = k("<script>")
                                .attr(i.scriptAttrs || {})
                                .prop({ charset: i.scriptCharset, src: i.url })
                                .on(
                                    "load error",
                                    (s = function (t) {
                                        n.remove(), (s = null), t && e("error" === t.type ? 404 : 200, t.type);
                                    })
                                )),
                                D.head.appendChild(n[0]);
                        },
                        abort: function () {
                            s && s();
                        },
                    };
            });
        var ii,
            ni = [],
            si = /(=)\?(?=&|$)|\?\?/;
        k.ajaxSetup({
            jsonp: "callback",
            jsonpCallback: function () {
                var t = ni.pop() || k.expando + "_" + Oe++;
                return (this[t] = !0), t;
            },
        }),
            k.ajaxPrefilter("json jsonp", function (t, e, i) {
                var n,
                    s,
                    o,
                    r = !1 !== t.jsonp && (si.test(t.url) ? "url" : "string" === typeof t.data && 0 === (t.contentType || "").indexOf("application/x-www-form-urlencoded") && si.test(t.data) && "data");
                if (r || "jsonp" === t.dataTypes[0])
                    return (
                        (n = t.jsonpCallback = b(t.jsonpCallback) ? t.jsonpCallback() : t.jsonpCallback),
                        r ? (t[r] = t[r].replace(si, "$1" + n)) : !1 !== t.jsonp && (t.url += (Ne.test(t.url) ? "&" : "?") + t.jsonp + "=" + n),
                        (t.converters["script json"] = function () {
                            return o || k.error(n + " was not called"), o[0];
                        }),
                        (t.dataTypes[0] = "json"),
                        (s = C[n]),
                        (C[n] = function () {
                            o = arguments;
                        }),
                        i.always(function () {
                            void 0 === s ? k(C).removeProp(n) : (C[n] = s), t[n] && ((t.jsonpCallback = e.jsonpCallback), ni.push(n)), o && b(s) && s(o[0]), (o = s = void 0);
                        }),
                        "script"
                    );
            }),
            (y.createHTMLDocument = (((ii = D.implementation.createHTMLDocument("").body).innerHTML = "<form></form><form></form>"), 2 === ii.childNodes.length)),
            (k.parseHTML = function (t, e, i) {
                return "string" !==    typeof t
                    ? []
                    : ("boolean" === typeof e && ((i = e), (e = !1)),
                      e || (y.createHTMLDocument ? (((n = (e = D.implementation.createHTMLDocument("")).createElement("base")).href = D.location.href), e.head.appendChild(n)) : (e = D)),
                      (o = !i && []),
                      (s = A.exec(t)) ? [e.createElement(s[1])] : ((s = _t([t], e, o)), o && o.length && k(o).remove(), k.merge([], s.childNodes)));
                var n, s, o;
            }),
            (k.fn.load = function (t, e, i) {
                var n,
                    s,
                    o,
                    r = this,
                    a = t.indexOf(" ");
                return (
                    -1 < a && ((n = Se(t.slice(a))), (t = t.slice(0, a))),
                    b(e) ? ((i = e), (e = void 0)) : e && "object" === typeof e && (s = "POST"),
                    0 < r.length &&
                        k
                            .ajax({ url: t, type: s || "GET", dataType: "html", data: e })
                            .done(function (t) {
                                (o = arguments), r.html(n ? k("<div>").append(k.parseHTML(t)).find(n) : t);
                            })
                            .always(
                                i &&
                                    function (t, e) {
                                        r.each(function () {
                                            i.apply(this, o || [t.responseText, e, t]);
                                        });
                                    }
                            ),
                    this
                );
            }),
            k.each(["ajaxStart", "ajaxStop", "ajaxComplete", "ajaxError", "ajaxSuccess", "ajaxSend"], function (t, e) {
                k.fn[e] = function (t) {
                    return this.on(e, t);
                };
            }),
            (k.expr.pseudos.animated = function (e) {
                return k.grep(k.timers, function (t) {
                    return e === t.elem;
                }).length;
            }),
            (k.offset = {
                setOffset: function (t, e, i) {
                    var n,
                        s,
                        o,
                        r,
                        a,
                        l,
                        c = k.css(t, "position"),
                        h = k(t),
                        u = {};
                    "static" === c && (t.style.position = "relative"),
                        (a = h.offset()),
                        (o = k.css(t, "top")),
                        (l = k.css(t, "left")),
                        (s = ("absolute" === c || "fixed" === c) && -1 < (o + l).indexOf("auto") ? ((r = (n = h.position()).top), n.left) : ((r = parseFloat(o) || 0), parseFloat(l) || 0)),
                        b(e) && (e = e.call(t, i, k.extend({}, a))),
                        null !==    e.top && (u.top = e.top - a.top + r),
                        null !==    e.left && (u.left = e.left - a.left + s),
                        "using" in e ? e.using.call(t, u) : h.css(u);
                },
            }),
            k.fn.extend({
                offset: function (e) {
                    if (arguments.length)
                        return void 0 === e
                            ? this
                            : this.each(function (t) {
                                  k.offset.setOffset(this, e, t);
                              });
                    var t,
                        i,
                        n = this[0];
                    return n ? (n.getClientRects().length ? ((t = n.getBoundingClientRect()), (i = n.ownerDocument.defaultView), { top: t.top + i.pageYOffset, left: t.left + i.pageXOffset }) : { top: 0, left: 0 }) : void 0;
                },
                position: function () {
                    if (this[0]) {
                        var t,
                            e,
                            i,
                            n = this[0],
                            s = { top: 0, left: 0 };
                        if ("fixed" === k.css(n, "position")) e = n.getBoundingClientRect();
                        else {
                            for (e = this.offset(), i = n.ownerDocument, t = n.offsetParent || i.documentElement; t && (t === i.body || t === i.documentElement) && "static" === k.css(t, "position"); ) t = t.parentNode;
                            t && t !== n && 1 === t.nodeType && (((s = k(t).offset()).top += k.css(t, "borderTopWidth", !0)), (s.left += k.css(t, "borderLeftWidth", !0)));
                        }
                        return { top: e.top - s.top - k.css(n, "marginTop", !0), left: e.left - s.left - k.css(n, "marginLeft", !0) };
                    }
                },
                offsetParent: function () {
                    return this.map(function () {
                        for (var t = this.offsetParent; t && "static" === k.css(t, "position"); ) t = t.offsetParent;
                        return t || st;
                    });
                },
            }),
            k.each({ scrollLeft: "pageXOffset", scrollTop: "pageYOffset" }, function (e, s) {
                var o = "pageYOffset" === s;
                k.fn[e] = function (t) {
                    return W(
                        this,
                        function (t, e, i) {
                            var n;
                            if ((g(t) ? (n = t) : 9 === t.nodeType && (n = t.defaultView), void 0 === i)) return n ? n[s] : t[e];
                            n ? n.scrollTo(o ? n.pageXOffset : i, o ? i : n.pageYOffset) : (t[e] = i);
                        },
                        e,
                        t,
                        arguments.length
                    );
                };
            }),
            k.each(["top", "left"], function (t, i) {
                k.cssHooks[i] = Jt(y.pixelPosition, function (t, e) {
                    if (e) return (e = Zt(t, i)), Vt.test(e) ? k(t).position()[i] + "px" : e;
                });
            }),
            k.each({ Height: "height", Width: "width" }, function (r, a) {
                k.each({ padding: "inner" + r, content: a, "": "outer" + r }, function (n, o) {
                    k.fn[o] = function (t, e) {
                        var i = arguments.length && (n || "boolean" !==    typeof t),
                            s = n || (!0 === t || !0 === e ? "margin" : "border");
                        return W(
                            this,
                            function (t, e, i) {
                                var n;
                                return g(t)
                                    ? 0 === o.indexOf("outer")
                                        ? t["inner" + r]
                                        : t.document.documentElement["client" + r]
                                    : 9 === t.nodeType
                                    ? ((n = t.documentElement), Math.max(t.body["scroll" + r], n["scroll" + r], t.body["offset" + r], n["offset" + r], n["client" + r]))
                                    : void 0 === i
                                    ? k.css(t, e, s)
                                    : k.style(t, e, i, s);
                            },
                            a,
                            i ? t : void 0,
                            i
                        );
                    };
                });
            }),
            k.each("blur focus focusin focusout resize scroll click dblclick mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave change select submit keydown keypress keyup contextmenu".split(" "), function (t, i) {
                k.fn[i] = function (t, e) {
                    return 0 < arguments.length ? this.on(i, null, t, e) : this.trigger(i);
                };
            }),
            k.fn.extend({
                hover: function (t, e) {
                    return this.mouseenter(t).mouseleave(e || t);
                },
            }),
            k.fn.extend({
                bind: function (t, e, i) {
                    return this.on(t, null, e, i);
                },
                unbind: function (t, e) {
                    return this.off(t, null, e);
                },
                delegate: function (t, e, i, n) {
                    return this.on(e, t, i, n);
                },
                undelegate: function (t, e, i) {
                    return 1 === arguments.length ? this.off(t, "**") : this.off(e, t || "**", i);
                },
            }),
            (k.proxy = function (t, e) {
                var i, n, s;
                if (("string" === typeof e && ((i = t[e]), (e = t), (t = i)), b(t)))
                    return (
                        (n = a.call(arguments, 2)),
                        ((s = function () {
                            return t.apply(e || this, n.concat(a.call(arguments)));
                        }).guid = t.guid = t.guid || k.guid++),
                        s
                    );
            }),
            (k.holdReady = function (t) {
                t ? k.readyWait++ : k.ready(!0);
            }),
            (k.isArray = Array.isArray),
            (k.parseJSON = JSON.parse),
            (k.nodeName = E),
            (k.isFunction = b),
            (k.isWindow = g),
            (k.camelCase = V),
            (k.type = _),
            (k.now = Date.now),
            (k.isNumeric = function (t) {
                var e = k.type(t);
                return ("number" === e || "string" === e) && !isNaN(t - parseFloat(t));
            }),
            "function" === typeof define &&
                define.amd &&
                define("jquery", [], function () {
                    return k;
                });
        var oi = C.jQuery,
            ri = C.$;
        return (
            (k.noConflict = function (t) {
                return C.$ === k && (C.$ = ri), t && C.jQuery === k && (C.jQuery = oi), k;
            }),
            t || (C.jQuery = C.$ = k),
            k
        );
    }),
    (function (t) {
        var e;
        if (("function" === typeof define && define.amd && (define(t), (e = !0)), "object" === typeof exports && ((module.exports = t()), (e = !0)), !e)) {
            var i = window.Cookies,
                n = (window.Cookies = t());
            n.noConflict = function () {
                return (window.Cookies = i), n;
            };
        }
    })(function () {
        function a() {
            for (var t = 0, e = {}; t < arguments.length; t++) {
                var i = arguments[t];
                for (var n in i) e[n] = i[n];
            }
            return e;
        }
        function c(t) {
            return t.replace(/(%[0-9A-Z]{2})+/g, decodeURIComponent);
        }
        return (function t(l) {
            function r() {}
            function i(t, e, i) {
                if ("undefined" !==    typeof document) {
                    "number" === typeof (i = a({ path: "/" }, r.defaults, i)).expires && (i.expires = new Date(+new Date() + 864e5 * i.expires)), (i.expires = i.expires ? i.expires.toUTCString() : "");
                    try {
                        var n = JSON.stringify(e);
                        /^[\{\[]/.test(n) && (e = n);
                    } catch (t) {}
                    (e = l.write ? l.write(e, t) : encodeURIComponent(String(e)).replace(/%(23|24|26|2B|3A|3C|3E|3D|2F|3F|40|5B|5D|5E|60|7B|7D|7C)/g, decodeURIComponent)),
                        (t = encodeURIComponent(String(t))
                            .replace(/%(23|24|26|2B|5E|60|7C)/g, decodeURIComponent)
                            .replace(/[\(\)]/g, escape));
                    var s = "";
                    for (var o in i) i[o] && ((s += "; " + o), !0 !== i[o] && (s += "=" + i[o].split(";")[0]));
                    return (document.cookie = t + "=" + e + s);
                }
            }
            function e(t, e) {
                if ("undefined" !==    typeof document) {
                    for (var i = {}, n = document.cookie ? document.cookie.split("; ") : [], s = 0; s < n.length; s++) {
                        var o = n[s].split("="),
                            r = o.slice(1).join("=");
                        e || '"' !== r.charAt(0) || (r = r.slice(1, -1));
                        try {
                            var a = c(o[0]);
                            if (((r = (l.read || l)(r, a) || c(r)), e))
                                try {
                                    r = JSON.parse(r);
                                } catch (t) {}
                            if (((i[a] = r), t === a)) break;
                        } catch (t) {}
                    }
                    return t ? i[t] : i;
                }
            }
            return (
                (r.set = i),
                (r.get = function (t) {
                    return e(t, !1);
                }),
                (r.getJSON = function (t) {
                    return e(t, !0);
                }),
                (r.remove = function (t, e) {
                    i(t, "", a(e, { expires: -1 }));
                }),
                (r.defaults = {}),
                (r.withConverter = t),
                r
            );
        })(function () {});
    }),
    (function (t) {
        "function" === typeof define && define.amd ? define(["jquery"], t) : t(jQuery);
    })(function (x) {
        x.ui = x.ui || {};
        x.ui.version = "1.12.1";
        var s,
            i = 0,
            a = Array.prototype.slice;
        (x.cleanData =
            ((s = x.cleanData),
            function (t) {
                var e, i, n;
                for (n = 0; null !==    (i = t[n]); n++)
                    try {
                        (e = x._data(i, "events")) && e.remove && x(i).triggerHandler("remove");
                    } catch (t) {}
                s(t);
            })),
            (x.widget = function (t, i, e) {
                var n,
                    s,
                    o,
                    r = {},
                    a = t.split(".")[0],
                    l = a + "-" + (t = t.split(".")[1]);
                return (
                    e || ((e = i), (i = x.Widget)),
                    x.isArray(e) && (e = x.extend.apply(null, [{}].concat(e))),
                    (x.expr[":"][l.toLowerCase()] = function (t) {
                        return !!x.data(t, l);
                    }),
                    (x[a] = x[a] || {}),
                    (n = x[a][t]),
                    (s = x[a][t] = function (t, e) {
                        if (!this._createWidget) return new s(t, e);
                        arguments.length && this._createWidget(t, e);
                    }),
                    x.extend(s, n, { version: e.version, _proto: x.extend({}, e), _childConstructors: [] }),
                    ((o = new i()).options = x.widget.extend({}, o.options)),
                    x.each(e, function (e, n) {
                        function s() {
                            return i.prototype[e].apply(this, arguments);
                        }
                        function o(t) {
                            return i.prototype[e].apply(this, t);
                        }
                        x.isFunction(n)
                            ? (r[e] = function () {
                                  var t,
                                      e = this._super,
                                      i = this._superApply;
                                  return (this._super = s), (this._superApply = o), (t = n.apply(this, arguments)), (this._super = e), (this._superApply = i), t;
                              })
                            : (r[e] = n);
                    }),
                    (s.prototype = x.widget.extend(o, { widgetEventPrefix: (n && o.widgetEventPrefix) || t }, r, { constructor: s, namespace: a, widgetName: t, widgetFullName: l })),
                    n
                        ? (x.each(n._childConstructors, function (t, e) {
                              var i = e.prototype;
                              x.widget(i.namespace + "." + i.widgetName, s, e._proto);
                          }),
                          delete n._childConstructors)
                        : i._childConstructors.push(s),
                    x.widget.bridge(t, s),
                    s
                );
            }),
            (x.widget.extend = function (t) {
                for (var e, i, n = a.call(arguments, 1), s = 0, o = n.length; s < o; s++)
                    for (e in n[s]) (i = n[s][e]), n[s].hasOwnProperty(e) && void 0 !== i && (x.isPlainObject(i) ? (t[e] = x.isPlainObject(t[e]) ? x.widget.extend({}, t[e], i) : x.widget.extend({}, i)) : (t[e] = i));
                return t;
            }),
            (x.widget.bridge = function (o, e) {
                var r = e.prototype.widgetFullName || o;
                x.fn[o] = function (i) {
                    var t = "string" === typeof i,
                        n = a.call(arguments, 1),
                        s = this;
                    return (
                        t
                            ? this.length || "instance" !== i
                                ? this.each(function () {
                                      var t,
                                          e = x.data(this, r);
                                      return "instance" === i
                                          ? ((s = e), !1)
                                          : e
                                          ? x.isFunction(e[i]) && "_" !== i.charAt(0)
                                              ? (t = e[i].apply(e, n)) !== e && void 0 !== t
                                                  ? ((s = t && t.jquery ? s.pushStack(t.get()) : t), !1)
                                                  : void 0
                                              : x.error("no such method '" + i + "' for " + o + " widget instance")
                                          : x.error("cannot call methods on " + o + " prior to initialization; attempted to call method '" + i + "'");
                                  })
                                : (s = void 0)
                            : (n.length && (i = x.widget.extend.apply(null, [i].concat(n))),
                              this.each(function () {
                                  var t = x.data(this, r);
                                  t ? (t.option(i || {}), t._init && t._init()) : x.data(this, r, new e(i, this));
                              })),
                        s
                    );
                };
            }),
            (x.Widget = function () {}),
            (x.Widget._childConstructors = []),
            (x.Widget.prototype = {
                widgetName: "widget",
                widgetEventPrefix: "",
                defaultElement: "<div>",
                options: { classes: {}, disabled: !1, create: null },
                _createWidget: function (t, e) {
                    (e = x(e || this.defaultElement || this)[0]),
                        (this.element = x(e)),
                        (this.uuid = i++),
                        (this.eventNamespace = "." + this.widgetName + this.uuid),
                        (this.bindings = x()),
                        (this.hoverable = x()),
                        (this.focusable = x()),
                        (this.classesElementLookup = {}),
                        e !== this &&
                            (x.data(e, this.widgetFullName, this),
                            this._on(!0, this.element, {
                                remove: function (t) {
                                    t.target === e && this.destroy();
                                },
                            }),
                            (this.document = x(e.style ? e.ownerDocument : e.document || e)),
                            (this.window = x(this.document[0].defaultView || this.document[0].parentWindow))),
                        (this.options = x.widget.extend({}, this.options, this._getCreateOptions(), t)),
                        this._create(),
                        this.options.disabled && this._setOptionDisabled(this.options.disabled),
                        this._trigger("create", null, this._getCreateEventData()),
                        this._init();
                },
                _getCreateOptions: function () {
                    return {};
                },
                _getCreateEventData: x.noop,
                _create: x.noop,
                _init: x.noop,
                destroy: function () {
                    var i = this;
                    this._destroy(),
                        x.each(this.classesElementLookup, function (t, e) {
                            i._removeClass(e, t);
                        }),
                        this.element.off(this.eventNamespace).removeData(this.widgetFullName),
                        this.widget().off(this.eventNamespace).removeAttr("aria-disabled"),
                        this.bindings.off(this.eventNamespace);
                },
                _destroy: x.noop,
                widget: function () {
                    return this.element;
                },
                option: function (t, e) {
                    var i,
                        n,
                        s,
                        o = t;
                    if (0 === arguments.length) return x.widget.extend({}, this.options);
                    if ("string" === typeof t)
                        if (((o = {}), (t = (i = t.split(".")).shift()), i.length)) {
                            for (n = o[t] = x.widget.extend({}, this.options[t]), s = 0; s < i.length - 1; s++) (n[i[s]] = n[i[s]] || {}), (n = n[i[s]]);
                            if (((t = i.pop()), 1 === arguments.length)) return void 0 === n[t] ? null : n[t];
                            n[t] = e;
                        } else {
                            if (1 === arguments.length) return void 0 === this.options[t] ? null : this.options[t];
                            o[t] = e;
                        }
                    return this._setOptions(o), this;
                },
                _setOptions: function (t) {
                    var e;
                    for (e in t) this._setOption(e, t[e]);
                    return this;
                },
                _setOption: function (t, e) {
                    return "classes" === t && this._setOptionClasses(e), (this.options[t] = e), "disabled" === t && this._setOptionDisabled(e), this;
                },
                _setOptionClasses: function (t) {
                    var e, i, n;
                    for (e in t) (n = this.classesElementLookup[e]), t[e] !== this.options.classes[e] && n && n.length && ((i = x(n.get())), this._removeClass(n, e), i.addClass(this._classes({ element: i, keys: e, classes: t, add: !0 })));
                },
                _setOptionDisabled: function (t) {
                    this._toggleClass(this.widget(), this.widgetFullName + "-disabled", null, !!t), t && (this._removeClass(this.hoverable, null, "ui-state-hover"), this._removeClass(this.focusable, null, "ui-state-focus"));
                },
                enable: function () {
                    return this._setOptions({ disabled: !1 });
                },
                disable: function () {
                    return this._setOptions({ disabled: !0 });
                },
                _classes: function (s) {
                    var o = [],
                        r = this;
                    function t(t, e) {
                        var i, n;
                        for (n = 0; n < t.length; n++)
                            (i = r.classesElementLookup[t[n]] || x()),
                                (i = s.add ? x(x.unique(i.get().concat(s.element.get()))) : x(i.not(s.element).get())),
                                (r.classesElementLookup[t[n]] = i),
                                o.push(t[n]),
                                e && s.classes[t[n]] && o.push(s.classes[t[n]]);
                    }
                    return (
                        (s = x.extend({ element: this.element, classes: this.options.classes || {} }, s)),
                        this._on(s.element, { remove: "_untrackClassesElement" }),
                        s.keys && t(s.keys.match(/\S+/g) || [], !0),
                        s.extra && t(s.extra.match(/\S+/g) || []),
                        o.join(" ")
                    );
                },
                _untrackClassesElement: function (i) {
                    var n = this;
                    x.each(n.classesElementLookup, function (t, e) {
                        -1 !== x.inArray(i.target, e) && (n.classesElementLookup[t] = x(e.not(i.target).get()));
                    });
                },
                _removeClass: function (t, e, i) {
                    return this._toggleClass(t, e, i, !1);
                },
                _addClass: function (t, e, i) {
                    return this._toggleClass(t, e, i, !0);
                },
                _toggleClass: function (t, e, i, n) {
                    n = "boolean" === typeof n ? n : i;
                    var s = "string" === typeof t || null === t,
                        o = { extra: s ? e : i, keys: s ? t : e, element: s ? this.element : t, add: n };
                    return o.element.toggleClass(this._classes(o), n), this;
                },
                _on: function (r, a, t) {
                    var l,
                        c = this;
                    "boolean" !==    typeof r && ((t = a), (a = r), (r = !1)),
                        t ? ((a = l = x(a)), (this.bindings = this.bindings.add(a))) : ((t = a), (a = this.element), (l = this.widget())),
                        x.each(t, function (t, e) {
                            function i() {
                                if (r || (!0 !== c.options.disabled && !x(this).hasClass("ui-state-disabled"))) return ("string" === typeof e ? c[e] : e).apply(c, arguments);
                            }
                            "string" !==    typeof e && (i.guid = e.guid = e.guid || i.guid || x.guid++);
                            var n = t.match(/^([\w:-]*)\s*(.*)$/),
                                s = n[1] + c.eventNamespace,
                                o = n[2];
                            o ? l.on(s, o, i) : a.on(s, i);
                        });
                },
                _off: function (t, e) {
                    (e = (e || "").split(" ").join(this.eventNamespace + " ") + this.eventNamespace),
                        t.off(e).off(e),
                        (this.bindings = x(this.bindings.not(t).get())),
                        (this.focusable = x(this.focusable.not(t).get())),
                        (this.hoverable = x(this.hoverable.not(t).get()));
                },
                _delay: function (t, e) {
                    var i = this;
                    return setTimeout(function () {
                        return ("string" === typeof t ? i[t] : t).apply(i, arguments);
                    }, e || 0);
                },
                _hoverable: function (t) {
                    (this.hoverable = this.hoverable.add(t)),
                        this._on(t, {
                            mouseenter: function (t) {
                                this._addClass(x(t.currentTarget), null, "ui-state-hover");
                            },
                            mouseleave: function (t) {
                                this._removeClass(x(t.currentTarget), null, "ui-state-hover");
                            },
                        });
                },
                _focusable: function (t) {
                    (this.focusable = this.focusable.add(t)),
                        this._on(t, {
                            focusin: function (t) {
                                this._addClass(x(t.currentTarget), null, "ui-state-focus");
                            },
                            focusout: function (t) {
                                this._removeClass(x(t.currentTarget), null, "ui-state-focus");
                            },
                        });
                },
                _trigger: function (t, e, i) {
                    var n,
                        s,
                        o = this.options[t];
                    if (((i = i || {}), ((e = x.Event(e)).type = (t === this.widgetEventPrefix ? t : this.widgetEventPrefix + t).toLowerCase()), (e.target = this.element[0]), (s = e.originalEvent))) for (n in s) n in e || (e[n] = s[n]);
                    return this.element.trigger(e, i), !((x.isFunction(o) && !1 === o.apply(this.element[0], [e].concat(i))) || e.isDefaultPrevented());
                },
            }),
            x.each({ show: "fadeIn", hide: "fadeOut" }, function (o, r) {
                x.Widget.prototype["_" + o] = function (e, t, i) {
                    var n;
                    "string" === typeof t && (t = { effect: t });
                    var s = t ? (!0 !== t && "number" !==    typeof t && t.effect) || r : o;
                    "number" === typeof (t = t || {}) && (t = { duration: t }),
                        (n = !x.isEmptyObject(t)),
                        (t.complete = i),
                        t.delay && e.delay(t.delay),
                        n && x.effects && x.effects.effect[s]
                            ? e[o](t)
                            : s !== o && e[s]
                            ? e[s](t.duration, t.easing, i)
                            : e.queue(function (t) {
                                  x(this)[o](), i && i.call(e[0]), t();
                              });
                };
            });
        var o, C, D, n, r, l, c, h, k;
        x.widget;
        function T(t, e, i) {
            return [parseFloat(t[0]) * (h.test(t[0]) ? e / 100 : 1), parseFloat(t[1]) * (h.test(t[1]) ? i / 100 : 1)];
        }
        function S(t, e) {
            return parseInt(x.css(t, e), 10) || 0;
        }
        (C = Math.max),
            (D = Math.abs),
            (n = /left|center|right/),
            (r = /top|center|bottom/),
            (l = /[\+\-]\d+(\.[\d]+)?%?/),
            (c = /^\w+/),
            (h = /%$/),
            (k = x.fn.position),
            (x.position = {
                scrollbarWidth: function () {
                    if (void 0 !== o) return o;
                    var t,
                        e,
                        i = x("<div style='display:block;position:absolute;width:50px;height:50px;overflow:hidden;'><div style='height:100px;width:auto;'></div></div>"),
                        n = i.children()[0];
                    return x("body").append(i), (t = n.offsetWidth), i.css("overflow", "scroll"), t === (e = n.offsetWidth) && (e = i[0].clientWidth), i.remove(), (o = t - e);
                },
                getScrollInfo: function (t) {
                    var e = t.isWindow || t.isDocument ? "" : t.element.css("overflow-x"),
                        i = t.isWindow || t.isDocument ? "" : t.element.css("overflow-y"),
                        n = "scroll" === e || ("auto" === e && t.width < t.element[0].scrollWidth);
                    return { width: "scroll" === i || ("auto" === i && t.height < t.element[0].scrollHeight) ? x.position.scrollbarWidth() : 0, height: n ? x.position.scrollbarWidth() : 0 };
                },
                getWithinInfo: function (t) {
                    var e = x(t || window),
                        i = x.isWindow(e[0]),
                        n = !!e[0] && 9 === e[0].nodeType;
                    return { element: e, isWindow: i, isDocument: n, offset: !i && !n ? x(t).offset() : { left: 0, top: 0 }, scrollLeft: e.scrollLeft(), scrollTop: e.scrollTop(), width: e.outerWidth(), height: e.outerHeight() };
                },
            }),
            (x.fn.position = function (u) {
                if (!u || !u.of) return k.apply(this, arguments);
                u = x.extend({}, u);
                var d,
                    p,
                    f,
                    g,
                    m,
                    t,
                    e,
                    i,
                    v = x(u.of),
                    y = x.position.getWithinInfo(u.within),
                    b = x.position.getScrollInfo(y),
                    w = (u.collision || "flip").split(" "),
                    _ = {};
                return (
                    (t =
                        9 === (i = (e = v)[0]).nodeType
                            ? { width: e.width(), height: e.height(), offset: { top: 0, left: 0 } }
                            : x.isWindow(i)
                            ? { width: e.width(), height: e.height(), offset: { top: e.scrollTop(), left: e.scrollLeft() } }
                            : i.preventDefault
                            ? { width: 0, height: 0, offset: { top: i.pageY, left: i.pageX } }
                            : { width: e.outerWidth(), height: e.outerHeight(), offset: e.offset() }),
                    v[0].preventDefault && (u.at = "left top"),
                    (p = t.width),
                    (f = t.height),
                    (g = t.offset),
                    (m = x.extend({}, g)),
                    x.each(["my", "at"], function () {
                        var t,
                            e,
                            i = (u[this] || "").split(" ");
                        1 === i.length && (i = n.test(i[0]) ? i.concat(["center"]) : r.test(i[0]) ? ["center"].concat(i) : ["center", "center"]),
                            (i[0] = n.test(i[0]) ? i[0] : "center"),
                            (i[1] = r.test(i[1]) ? i[1] : "center"),
                            (t = l.exec(i[0])),
                            (e = l.exec(i[1])),
                            (_[this] = [t ? t[0] : 0, e ? e[0] : 0]),
                            (u[this] = [c.exec(i[0])[0], c.exec(i[1])[0]]);
                    }),
                    1 === w.length && (w[1] = w[0]),
                    "right" === u.at[0] ? (m.left += p) : "center" === u.at[0] && (m.left += p / 2),
                    "bottom" === u.at[1] ? (m.top += f) : "center" === u.at[1] && (m.top += f / 2),
                    (d = T(_.at, p, f)),
                    (m.left += d[0]),
                    (m.top += d[1]),
                    this.each(function () {
                        var i,
                            t,
                            r = x(this),
                            a = r.outerWidth(),
                            l = r.outerHeight(),
                            e = S(this, "marginLeft"),
                            n = S(this, "marginTop"),
                            s = a + e + S(this, "marginRight") + b.width,
                            o = l + n + S(this, "marginBottom") + b.height,
                            c = x.extend({}, m),
                            h = T(_.my, r.outerWidth(), r.outerHeight());
                        "right" === u.my[0] ? (c.left -= a) : "center" === u.my[0] && (c.left -= a / 2),
                            "bottom" === u.my[1] ? (c.top -= l) : "center" === u.my[1] && (c.top -= l / 2),
                            (c.left += h[0]),
                            (c.top += h[1]),
                            (i = { marginLeft: e, marginTop: n }),
                            x.each(["left", "top"], function (t, e) {
                                x.ui.position[w[t]] &&
                                    x.ui.position[w[t]][e](c, {
                                        targetWidth: p,
                                        targetHeight: f,
                                        elemWidth: a,
                                        elemHeight: l,
                                        collisionPosition: i,
                                        collisionWidth: s,
                                        collisionHeight: o,
                                        offset: [d[0] + h[0], d[1] + h[1]],
                                        my: u.my,
                                        at: u.at,
                                        within: y,
                                        elem: r,
                                    });
                            }),
                            u.using &&
                                (t = function (t) {
                                    var e = g.left - c.left,
                                        i = e + p - a,
                                        n = g.top - c.top,
                                        s = n + f - l,
                                        o = {
                                            target: { element: v, left: g.left, top: g.top, width: p, height: f },
                                            element: { element: r, left: c.left, top: c.top, width: a, height: l },
                                            horizontal: i < 0 ? "left" : 0 < e ? "right" : "center",
                                            vertical: s < 0 ? "top" : 0 < n ? "bottom" : "middle",
                                        };
                                    p < a && D(e + i) < p && (o.horizontal = "center"),
                                        f < l && D(n + s) < f && (o.vertical = "middle"),
                                        C(D(e), D(i)) > C(D(n), D(s)) ? (o.important = "horizontal") : (o.important = "vertical"),
                                        u.using.call(this, t, o);
                                }),
                            r.offset(x.extend(c, { using: t }));
                    })
                );
            }),
            (x.ui.position = {
                fit: {
                    left: function (t, e) {
                        var i,
                            n = e.within,
                            s = n.isWindow ? n.scrollLeft : n.offset.left,
                            o = n.width,
                            r = t.left - e.collisionPosition.marginLeft,
                            a = s - r,
                            l = r + e.collisionWidth - o - s;
                        e.collisionWidth > o
                            ? 0 < a && l <= 0
                                ? ((i = t.left + a + e.collisionWidth - o - s), (t.left += a - i))
                                : (t.left = !(0 < l && a <= 0) && l < a ? s + o - e.collisionWidth : s)
                            : 0 < a
                            ? (t.left += a)
                            : 0 < l
                            ? (t.left -= l)
                            : (t.left = C(t.left - r, t.left));
                    },
                    top: function (t, e) {
                        var i,
                            n = e.within,
                            s = n.isWindow ? n.scrollTop : n.offset.top,
                            o = e.within.height,
                            r = t.top - e.collisionPosition.marginTop,
                            a = s - r,
                            l = r + e.collisionHeight - o - s;
                        e.collisionHeight > o
                            ? 0 < a && l <= 0
                                ? ((i = t.top + a + e.collisionHeight - o - s), (t.top += a - i))
                                : (t.top = !(0 < l && a <= 0) && l < a ? s + o - e.collisionHeight : s)
                            : 0 < a
                            ? (t.top += a)
                            : 0 < l
                            ? (t.top -= l)
                            : (t.top = C(t.top - r, t.top));
                    },
                },
                flip: {
                    left: function (t, e) {
                        var i,
                            n,
                            s = e.within,
                            o = s.offset.left + s.scrollLeft,
                            r = s.width,
                            a = s.isWindow ? s.scrollLeft : s.offset.left,
                            l = t.left - e.collisionPosition.marginLeft,
                            c = l - a,
                            h = l + e.collisionWidth - r - a,
                            u = "left" === e.my[0] ? -e.elemWidth : "right" === e.my[0] ? e.elemWidth : 0,
                            d = "left" === e.at[0] ? e.targetWidth : "right" === e.at[0] ? -e.targetWidth : 0,
                            p = -2 * e.offset[0];
                        c < 0
                            ? ((i = t.left + u + d + p + e.collisionWidth - r - o) < 0 || i < D(c)) && (t.left += u + d + p)
                            : 0 < h && (0 < (n = t.left - e.collisionPosition.marginLeft + u + d + p - a) || D(n) < h) && (t.left += u + d + p);
                    },
                    top: function (t, e) {
                        var i,
                            n,
                            s = e.within,
                            o = s.offset.top + s.scrollTop,
                            r = s.height,
                            a = s.isWindow ? s.scrollTop : s.offset.top,
                            l = t.top - e.collisionPosition.marginTop,
                            c = l - a,
                            h = l + e.collisionHeight - r - a,
                            u = "top" === e.my[1] ? -e.elemHeight : "bottom" === e.my[1] ? e.elemHeight : 0,
                            d = "top" === e.at[1] ? e.targetHeight : "bottom" === e.at[1] ? -e.targetHeight : 0,
                            p = -2 * e.offset[1];
                        c < 0 ? ((n = t.top + u + d + p + e.collisionHeight - r - o) < 0 || n < D(c)) && (t.top += u + d + p) : 0 < h && (0 < (i = t.top - e.collisionPosition.marginTop + u + d + p - a) || D(i) < h) && (t.top += u + d + p);
                    },
                },
                flipfit: {
                    left: function () {
                        x.ui.position.flip.left.apply(this, arguments), x.ui.position.fit.left.apply(this, arguments);
                    },
                    top: function () {
                        x.ui.position.flip.top.apply(this, arguments), x.ui.position.fit.top.apply(this, arguments);
                    },
                },
            });
        var t;
        x.ui.position,
            x.extend(x.expr[":"], {
                data: x.expr.createPseudo
                    ? x.expr.createPseudo(function (e) {
                          return function (t) {
                              return !!x.data(t, e);
                          };
                      })
                    : function (t, e, i) {
                          return !!x.data(t, i[3]);
                      },
            }),
            x.fn.extend({
                disableSelection:
                    ((t = "onselectstart" in document.createElement("div") ? "selectstart" : "mousedown"),
                    function () {
                        return this.on(t + ".ui-disableSelection", function (t) {
                            t.preventDefault();
                        });
                    }),
                enableSelection: function () {
                    return this.off(".ui-disableSelection");
                },
            });
        (x.ui.focusable = function (t, e) {
            var i,
                n,
                s,
                o,
                r,
                a = t.nodeName.toLowerCase();
            return "area" === a
                ? ((n = (i = t.parentNode).name), !(!t.href || !n || "map" !== i.nodeName.toLowerCase()) && 0 < (s = x("img[usemap='#" + n + "']")).length && s.is(":visible"))
                : (/^(input|select|textarea|button|object)$/.test(a) ? (o = !t.disabled) && (r = x(t).closest("fieldset")[0]) && (o = !r.disabled) : (o = ("a" === a && t.href) || e),
                  o &&
                      x(t).is(":visible") &&
                      (function (t) {
                          var e = t.css("visibility");
                          for (; "inherit" === e; ) (t = t.parent()), (e = t.css("visibility"));
                          return "hidden" !== e;
                      })(x(t)));
        }),
            x.extend(x.expr[":"], {
                focusable: function (t) {
                    return x.ui.focusable(t, null !==    x.attr(t, "tabindex"));
                },
            });
        x.ui.focusable,
            (x.fn.form = function () {
                return "string" === typeof this[0].form ? this.closest("form") : x(this[0].form);
            }),
            (x.ui.formResetMixin = {
                _formResetHandler: function () {
                    var e = x(this);
                    setTimeout(function () {
                        var t = e.data("ui-form-reset-instances");
                        x.each(t, function () {
                            this.refresh();
                        });
                    });
                },
                _bindFormResetHandler: function () {
                    if (((this.form = this.element.form()), this.form.length)) {
                        var t = this.form.data("ui-form-reset-instances") || [];
                        t.length || this.form.on("reset.ui-form-reset", this._formResetHandler), t.push(this), this.form.data("ui-form-reset-instances", t);
                    }
                },
                _unbindFormResetHandler: function () {
                    if (this.form.length) {
                        var t = this.form.data("ui-form-reset-instances");
                        t.splice(x.inArray(this, t), 1), t.length ? this.form.data("ui-form-reset-instances", t) : this.form.removeData("ui-form-reset-instances").off("reset.ui-form-reset");
                    }
                },
            });
        "1.7" === x.fn.jquery.substring(0, 3) &&
            (x.each(["Width", "Height"], function (t, i) {
                var s = "Width" === i ? ["Left", "Right"] : ["Top", "Bottom"],
                    n = i.toLowerCase(),
                    o = { innerWidth: x.fn.innerWidth, innerHeight: x.fn.innerHeight, outerWidth: x.fn.outerWidth, outerHeight: x.fn.outerHeight };
                function r(t, e, i, n) {
                    return (
                        x.each(s, function () {
                            (e -= parseFloat(x.css(t, "padding" + this)) || 0), i && (e -= parseFloat(x.css(t, "border" + this + "Width")) || 0), n && (e -= parseFloat(x.css(t, "margin" + this)) || 0);
                        }),
                        e
                    );
                }
                (x.fn["inner" + i] = function (t) {
                    return void 0 === t
                        ? o["inner" + i].call(this)
                        : this.each(function () {
                              x(this).css(n, r(this, t) + "px");
                          });
                }),
                    (x.fn["outer" + i] = function (t, e) {
                        return "number" !==    typeof t
                            ? o["outer" + i].call(this, t)
                            : this.each(function () {
                                  x(this).css(n, r(this, t, !0, e) + "px");
                              });
                    });
            }),
            (x.fn.addBack = function (t) {
                return this.add(null === t ? this.prevObject : this.prevObject.filter(t));
            }));
        (x.ui.keyCode = { BACKSPACE: 8, COMMA: 188, DELETE: 46, DOWN: 40, END: 35, ENTER: 13, ESCAPE: 27, HOME: 36, LEFT: 37, PAGE_DOWN: 34, PAGE_UP: 33, PERIOD: 190, RIGHT: 39, SPACE: 32, TAB: 9, UP: 38 }),
            (x.ui.escapeSelector =
                ((e = /([!"#$%&'()*+,./:;<=>?@[\]^`{|}~])/g),
                function (t) {
                    return t.replace(e, "\\$1");
                })),
            (x.fn.labels = function () {
                var t, e, i, n, s;
                return this[0].labels && this[0].labels.length
                    ? this.pushStack(this[0].labels)
                    : ((n = this.eq(0).parents("label")),
                      (i = this.attr("id")) && ((s = (t = this.eq(0).parents().last()).add(t.length ? t.siblings() : this.siblings())), (e = "label[for='" + x.ui.escapeSelector(i) + "']"), (n = n.add(s.find(e).addBack(e)))),
                      this.pushStack(n));
            }),
            (x.fn.scrollParent = function (t) {
                var e = this.css("position"),
                    i = "absolute" === e,
                    n = t ? /(auto|scroll|hidden)/ : /(auto|scroll)/,
                    s = this.parents()
                        .filter(function () {
                            var t = x(this);
                            return (!i || "static" !== t.css("position")) && n.test(t.css("overflow") + t.css("overflow-y") + t.css("overflow-x"));
                        })
                        .eq(0);
                return "fixed" !== e && s.length ? s : x(this[0].ownerDocument || document);
            }),
            x.extend(x.expr[":"], {
                tabbable: function (t) {
                    var e = x.attr(t, "tabindex"),
                        i = null !==    e;
                    return (!i || 0 <= e) && x.ui.focusable(t, i);
                },
            }),
            x.fn.extend({
                uniqueId:
                    ((u = 0),
                    function () {
                        return this.each(function () {
                            this.id || (this.id = "ui-id-" + ++u);
                        });
                    }),
                removeUniqueId: function () {
                    return this.each(function () {
                        /^ui-id-\d+$/.test(this.id) && x(this).removeAttr("id");
                    });
                },
            }),
            (x.ui.ie = !!/msie [\w.]+/.exec(navigator.userAgent.toLowerCase()));
        var e,
            u,
            d = !1;
        x(document).on("mouseup", function () {
            d = !1;
        });
        x.widget("ui.mouse", {
            version: "1.12.1",
            options: { cancel: "input, textarea, button, select, option", distance: 1, delay: 0 },
            _mouseInit: function () {
                var e = this;
                this.element
                    .on("mousedown." + this.widgetName, function (t) {
                        return e._mouseDown(t);
                    })
                    .on("click." + this.widgetName, function (t) {
                        if (!0 === x.data(t.target, e.widgetName + ".preventClickEvent")) return x.removeData(t.target, e.widgetName + ".preventClickEvent"), t.stopImmediatePropagation(), !1;
                    }),
                    (this.started = !1);
            },
            _mouseDestroy: function () {
                this.element.off("." + this.widgetName), this._mouseMoveDelegate && this.document.off("mousemove." + this.widgetName, this._mouseMoveDelegate).off("mouseup." + this.widgetName, this._mouseUpDelegate);
            },
            _mouseDown: function (t) {
                if (!d) {
                    (this._mouseMoved = !1), this._mouseStarted && this._mouseUp(t), (this._mouseDownEvent = t);
                    var e = this,
                        i = 1 === t.which,
                        n = !("string" !==    typeof this.options.cancel || !t.target.nodeName) && x(t.target).closest(this.options.cancel).length;
                    return i && !n && this._mouseCapture(t)
                        ? ((this.mouseDelayMet = !this.options.delay),
                          this.mouseDelayMet ||
                              (this._mouseDelayTimer = setTimeout(function () {
                                  e.mouseDelayMet = !0;
                              }, this.options.delay)),
                          this._mouseDistanceMet(t) && this._mouseDelayMet(t) && ((this._mouseStarted = !1 !== this._mouseStart(t)), !this._mouseStarted)
                              ? (t.preventDefault(), !0)
                              : (!0 === x.data(t.target, this.widgetName + ".preventClickEvent") && x.removeData(t.target, this.widgetName + ".preventClickEvent"),
                                (this._mouseMoveDelegate = function (t) {
                                    return e._mouseMove(t);
                                }),
                                (this._mouseUpDelegate = function (t) {
                                    return e._mouseUp(t);
                                }),
                                this.document.on("mousemove." + this.widgetName, this._mouseMoveDelegate).on("mouseup." + this.widgetName, this._mouseUpDelegate),
                                t.preventDefault(),
                                (d = !0)))
                        : !0;
                }
            },
            _mouseMove: function (t) {
                if (this._mouseMoved) {
                    if (x.ui.ie && (!document.documentMode || document.documentMode < 9) && !t.button) return this._mouseUp(t);
                    if (!t.which)
                        if (t.originalEvent.altKey || t.originalEvent.ctrlKey || t.originalEvent.metaKey || t.originalEvent.shiftKey) this.ignoreMissingWhich = !0;
                        else if (!this.ignoreMissingWhich) return this._mouseUp(t);
                }
                return (
                    (t.which || t.button) && (this._mouseMoved = !0),
                    this._mouseStarted
                        ? (this._mouseDrag(t), t.preventDefault())
                        : (this._mouseDistanceMet(t) && this._mouseDelayMet(t) && ((this._mouseStarted = !1 !== this._mouseStart(this._mouseDownEvent, t)), this._mouseStarted ? this._mouseDrag(t) : this._mouseUp(t)), !this._mouseStarted)
                );
            },
            _mouseUp: function (t) {
                this.document.off("mousemove." + this.widgetName, this._mouseMoveDelegate).off("mouseup." + this.widgetName, this._mouseUpDelegate),
                    this._mouseStarted && ((this._mouseStarted = !1), t.target === this._mouseDownEvent.target && x.data(t.target, this.widgetName + ".preventClickEvent", !0), this._mouseStop(t)),
                    this._mouseDelayTimer && (clearTimeout(this._mouseDelayTimer), delete this._mouseDelayTimer),
                    (this.ignoreMissingWhich = !1),
                    (d = !1),
                    t.preventDefault();
            },
            _mouseDistanceMet: function (t) {
                return Math.max(Math.abs(this._mouseDownEvent.pageX - t.pageX), Math.abs(this._mouseDownEvent.pageY - t.pageY)) >= this.options.distance;
            },
            _mouseDelayMet: function () {
                return this.mouseDelayMet;
            },
            _mouseStart: function () {},
            _mouseDrag: function () {},
            _mouseStop: function () {},
            _mouseCapture: function () {
                return !0;
            },
        }),
            (x.ui.plugin = {
                add: function (t, e, i) {
                    var n,
                        s = x.ui[t].prototype;
                    for (n in i) (s.plugins[n] = s.plugins[n] || []), s.plugins[n].push([e, i[n]]);
                },
                call: function (t, e, i, n) {
                    var s,
                        o = t.plugins[e];
                    if (o && (n || (t.element[0].parentNode && 11 !== t.element[0].parentNode.nodeType))) for (s = 0; s < o.length; s++) t.options[o[s][0]] && o[s][1].apply(t.element, i);
                },
            }),
            (x.ui.safeActiveElement = function (e) {
                var i;
                try {
                    i = e.activeElement;
                } catch (t) {
                    i = e.body;
                }
                return (i = i || e.body).nodeName || (i = e.body), i;
            }),
            (x.ui.safeBlur = function (t) {
                t && "body" !== t.nodeName.toLowerCase() && x(t).trigger("blur");
            });
        x.widget("ui.draggable", x.ui.mouse, {
            version: "1.12.1",
            widgetEventPrefix: "drag",
            options: {
                addClasses: !0,
                appendTo: "parent",
                axis: !1,
                connectToSortable: !1,
                containment: !1,
                cursor: "auto",
                cursorAt: !1,
                grid: !1,
                handle: !1,
                helper: "original",
                iframeFix: !1,
                opacity: !1,
                refreshPositions: !1,
                revert: !1,
                revertDuration: 500,
                scope: "default",
                scroll: !0,
                scrollSensitivity: 20,
                scrollSpeed: 20,
                snap: !1,
                snapMode: "both",
                snapTolerance: 20,
                stack: !1,
                zIndex: !1,
                drag: null,
                start: null,
                stop: null,
            },
            _create: function () {
                "original" === this.options.helper && this._setPositionRelative(), this.options.addClasses && this._addClass("ui-draggable"), this._setHandleClassName(), this._mouseInit();
            },
            _setOption: function (t, e) {
                this._super(t, e), "handle" === t && (this._removeHandleClassName(), this._setHandleClassName());
            },
            _destroy: function () {
                (this.helper || this.element).is(".ui-draggable-dragging") ? (this.destroyOnClear = !0) : (this._removeHandleClassName(), this._mouseDestroy());
            },
            _mouseCapture: function (t) {
                var e = this.options;
                return (
                    !(this.helper || e.disabled || 0 < x(t.target).closest(".ui-resizable-handle").length) &&
                    ((this.handle = this._getHandle(t)), !!this.handle && (this._blurActiveElement(t), this._blockFrames(!0 === e.iframeFix ? "iframe" : e.iframeFix), !0))
                );
            },
            _blockFrames: function (t) {
                this.iframeBlocks = this.document.find(t).map(function () {
                    var t = x(this);
                    return x("<div>").css("position", "absolute").appendTo(t.parent()).outerWidth(t.outerWidth()).outerHeight(t.outerHeight()).offset(t.offset())[0];
                });
            },
            _unblockFrames: function () {
                this.iframeBlocks && (this.iframeBlocks.remove(), delete this.iframeBlocks);
            },
            _blurActiveElement: function (t) {
                var e = x.ui.safeActiveElement(this.document[0]);
                x(t.target).closest(e).length || x.ui.safeBlur(e);
            },
            _mouseStart: function (t) {
                var e = this.options;
                return (
                    (this.helper = this._createHelper(t)),
                    this._addClass(this.helper, "ui-draggable-dragging"),
                    this._cacheHelperProportions(),
                    x.ui.ddmanager && (x.ui.ddmanager.current = this),
                    this._cacheMargins(),
                    (this.cssPosition = this.helper.css("position")),
                    (this.scrollParent = this.helper.scrollParent(!0)),
                    (this.offsetParent = this.helper.offsetParent()),
                    (this.hasFixedAncestor =
                        0 <
                        this.helper.parents().filter(function () {
                            return "fixed" === x(this).css("position");
                        }).length),
                    (this.positionAbs = this.element.offset()),
                    this._refreshOffsets(t),
                    (this.originalPosition = this.position = this._generatePosition(t, !1)),
                    (this.originalPageX = t.pageX),
                    (this.originalPageY = t.pageY),
                    e.cursorAt && this._adjustOffsetFromHelper(e.cursorAt),
                    this._setContainment(),
                    !1 === this._trigger("start", t)
                        ? (this._clear(), !1)
                        : (this._cacheHelperProportions(), x.ui.ddmanager && !e.dropBehaviour && x.ui.ddmanager.prepareOffsets(this, t), this._mouseDrag(t, !0), x.ui.ddmanager && x.ui.ddmanager.dragStart(this, t), !0)
                );
            },
            _refreshOffsets: function (t) {
                (this.offset = { top: this.positionAbs.top - this.margins.top, left: this.positionAbs.left - this.margins.left, scroll: !1, parent: this._getParentOffset(), relative: this._getRelativeOffset() }),
                    (this.offset.click = { left: t.pageX - this.offset.left, top: t.pageY - this.offset.top });
            },
            _mouseDrag: function (t, e) {
                if ((this.hasFixedAncestor && (this.offset.parent = this._getParentOffset()), (this.position = this._generatePosition(t, !0)), (this.positionAbs = this._convertPositionTo("absolute")), !e)) {
                    var i = this._uiHash();
                    if (!1 === this._trigger("drag", t, i)) return this._mouseUp(new x.Event("mouseup", t)), !1;
                    this.position = i.position;
                }
                return (this.helper[0].style.left = this.position.left + "px"), (this.helper[0].style.top = this.position.top + "px"), x.ui.ddmanager && x.ui.ddmanager.drag(this, t), !1;
            },
            _mouseStop: function (t) {
                var e = this,
                    i = !1;
                return (
                    x.ui.ddmanager && !this.options.dropBehaviour && (i = x.ui.ddmanager.drop(this, t)),
                    this.dropped && ((i = this.dropped), (this.dropped = !1)),
                    ("invalid" === this.options.revert && !i) || ("valid" === this.options.revert && i) || !0 === this.options.revert || (x.isFunction(this.options.revert) && this.options.revert.call(this.element, i))
                        ? x(this.helper).animate(this.originalPosition, parseInt(this.options.revertDuration, 10), function () {
                              !1 !== e._trigger("stop", t) && e._clear();
                          })
                        : !1 !== this._trigger("stop", t) && this._clear(),
                    !1
                );
            },
            _mouseUp: function (t) {
                return this._unblockFrames(), x.ui.ddmanager && x.ui.ddmanager.dragStop(this, t), this.handleElement.is(t.target) && this.element.trigger("focus"), x.ui.mouse.prototype._mouseUp.call(this, t);
            },
            cancel: function () {
                return this.helper.is(".ui-draggable-dragging") ? this._mouseUp(new x.Event("mouseup", { target: this.element[0] })) : this._clear(), this;
            },
            _getHandle: function (t) {
                return !this.options.handle || !!x(t.target).closest(this.element.find(this.options.handle)).length;
            },
            _setHandleClassName: function () {
                (this.handleElement = this.options.handle ? this.element.find(this.options.handle) : this.element), this._addClass(this.handleElement, "ui-draggable-handle");
            },
            _removeHandleClassName: function () {
                this._removeClass(this.handleElement, "ui-draggable-handle");
            },
            _createHelper: function (t) {
                var e = this.options,
                    i = x.isFunction(e.helper),
                    n = i ? x(e.helper.apply(this.element[0], [t])) : "clone" === e.helper ? this.element.clone().removeAttr("id") : this.element;
                return (
                    n.parents("body").length || n.appendTo("parent" === e.appendTo ? this.element[0].parentNode : e.appendTo),
                    i && n[0] === this.element[0] && this._setPositionRelative(),
                    n[0] === this.element[0] || /(fixed|absolute)/.test(n.css("position")) || n.css("position", "absolute"),
                    n
                );
            },
            _setPositionRelative: function () {
                /^(?:r|a|f)/.test(this.element.css("position")) || (this.element[0].style.position = "relative");
            },
            _adjustOffsetFromHelper: function (t) {
                "string" === typeof t && (t = t.split(" ")),
                    x.isArray(t) && (t = { left: +t[0], top: +t[1] || 0 }),
                    "left" in t && (this.offset.click.left = t.left + this.margins.left),
                    "right" in t && (this.offset.click.left = this.helperProportions.width - t.right + this.margins.left),
                    "top" in t && (this.offset.click.top = t.top + this.margins.top),
                    "bottom" in t && (this.offset.click.top = this.helperProportions.height - t.bottom + this.margins.top);
            },
            _isRootNode: function (t) {
                return /(html|body)/i.test(t.tagName) || t === this.document[0];
            },
            _getParentOffset: function () {
                var t = this.offsetParent.offset(),
                    e = this.document[0];
                return (
                    "absolute" === this.cssPosition && this.scrollParent[0] !== e && x.contains(this.scrollParent[0], this.offsetParent[0]) && ((t.left += this.scrollParent.scrollLeft()), (t.top += this.scrollParent.scrollTop())),
                    this._isRootNode(this.offsetParent[0]) && (t = { top: 0, left: 0 }),
                    { top: t.top + (parseInt(this.offsetParent.css("borderTopWidth"), 10) || 0), left: t.left + (parseInt(this.offsetParent.css("borderLeftWidth"), 10) || 0) }
                );
            },
            _getRelativeOffset: function () {
                if ("relative" !== this.cssPosition) return { top: 0, left: 0 };
                var t = this.element.position(),
                    e = this._isRootNode(this.scrollParent[0]);
                return { top: t.top - (parseInt(this.helper.css("top"), 10) || 0) + (e ? 0 : this.scrollParent.scrollTop()), left: t.left - (parseInt(this.helper.css("left"), 10) || 0) + (e ? 0 : this.scrollParent.scrollLeft()) };
            },
            _cacheMargins: function () {
                this.margins = {
                    left: parseInt(this.element.css("marginLeft"), 10) || 0,
                    top: parseInt(this.element.css("marginTop"), 10) || 0,
                    right: parseInt(this.element.css("marginRight"), 10) || 0,
                    bottom: parseInt(this.element.css("marginBottom"), 10) || 0,
                };
            },
            _cacheHelperProportions: function () {
                this.helperProportions = { width: this.helper.outerWidth(), height: this.helper.outerHeight() };
            },
            _setContainment: function () {
                var t,
                    e,
                    i,
                    n = this.options,
                    s = this.document[0];
                (this.relativeContainer = null),
                    n.containment
                        ? "window" !== n.containment
                            ? "document" !== n.containment
                                ? n.containment.constructor !== Array
                                    ? ("parent" === n.containment && (n.containment = this.helper[0].parentNode),
                                      (i = (e = x(n.containment))[0]) &&
                                          ((t = /(scroll|auto)/.test(e.css("overflow"))),
                                          (this.containment = [
                                              (parseInt(e.css("borderLeftWidth"), 10) || 0) + (parseInt(e.css("paddingLeft"), 10) || 0),
                                              (parseInt(e.css("borderTopWidth"), 10) || 0) + (parseInt(e.css("paddingTop"), 10) || 0),
                                              (t ? Math.max(i.scrollWidth, i.offsetWidth) : i.offsetWidth) -
                                                  (parseInt(e.css("borderRightWidth"), 10) || 0) -
                                                  (parseInt(e.css("paddingRight"), 10) || 0) -
                                                  this.helperProportions.width -
                                                  this.margins.left -
                                                  this.margins.right,
                                              (t ? Math.max(i.scrollHeight, i.offsetHeight) : i.offsetHeight) -
                                                  (parseInt(e.css("borderBottomWidth"), 10) || 0) -
                                                  (parseInt(e.css("paddingBottom"), 10) || 0) -
                                                  this.helperProportions.height -
                                                  this.margins.top -
                                                  this.margins.bottom,
                                          ]),
                                          (this.relativeContainer = e)))
                                    : (this.containment = n.containment)
                                : (this.containment = [0, 0, x(s).width() - this.helperProportions.width - this.margins.left, (x(s).height() || s.body.parentNode.scrollHeight) - this.helperProportions.height - this.margins.top])
                            : (this.containment = [
                                  x(window).scrollLeft() - this.offset.relative.left - this.offset.parent.left,
                                  x(window).scrollTop() - this.offset.relative.top - this.offset.parent.top,
                                  x(window).scrollLeft() + x(window).width() - this.helperProportions.width - this.margins.left,
                                  x(window).scrollTop() + (x(window).height() || s.body.parentNode.scrollHeight) - this.helperProportions.height - this.margins.top,
                              ])
                        : (this.containment = null);
            },
            _convertPositionTo: function (t, e) {
                e = e || this.position;
                var i = "absolute" === t ? 1 : -1,
                    n = this._isRootNode(this.scrollParent[0]);
                return {
                    top: e.top + this.offset.relative.top * i + this.offset.parent.top * i - ("fixed" === this.cssPosition ? -this.offset.scroll.top : n ? 0 : this.offset.scroll.top) * i,
                    left: e.left + this.offset.relative.left * i + this.offset.parent.left * i - ("fixed" === this.cssPosition ? -this.offset.scroll.left : n ? 0 : this.offset.scroll.left) * i,
                };
            },
            _generatePosition: function (t, e) {
                var i,
                    n,
                    s,
                    o,
                    r = this.options,
                    a = this._isRootNode(this.scrollParent[0]),
                    l = t.pageX,
                    c = t.pageY;
                return (
                    (a && this.offset.scroll) || (this.offset.scroll = { top: this.scrollParent.scrollTop(), left: this.scrollParent.scrollLeft() }),
                    e &&
                        (this.containment &&
                            ((i = this.relativeContainer ? ((n = this.relativeContainer.offset()), [this.containment[0] + n.left, this.containment[1] + n.top, this.containment[2] + n.left, this.containment[3] + n.top]) : this.containment),
                            t.pageX - this.offset.click.left < i[0] && (l = i[0] + this.offset.click.left),
                            t.pageY - this.offset.click.top < i[1] && (c = i[1] + this.offset.click.top),
                            t.pageX - this.offset.click.left > i[2] && (l = i[2] + this.offset.click.left),
                            t.pageY - this.offset.click.top > i[3] && (c = i[3] + this.offset.click.top)),
                        r.grid &&
                            ((s = r.grid[1] ? this.originalPageY + Math.round((c - this.originalPageY) / r.grid[1]) * r.grid[1] : this.originalPageY),
                            (c = !i || s - this.offset.click.top >= i[1] || s - this.offset.click.top > i[3] ? s : s - this.offset.click.top >= i[1] ? s - r.grid[1] : s + r.grid[1]),
                            (o = r.grid[0] ? this.originalPageX + Math.round((l - this.originalPageX) / r.grid[0]) * r.grid[0] : this.originalPageX),
                            (l = !i || o - this.offset.click.left >= i[0] || o - this.offset.click.left > i[2] ? o : o - this.offset.click.left >= i[0] ? o - r.grid[0] : o + r.grid[0])),
                        "y" === r.axis && (l = this.originalPageX),
                        "x" === r.axis && (c = this.originalPageY)),
                    {
                        top: c - this.offset.click.top - this.offset.relative.top - this.offset.parent.top + ("fixed" === this.cssPosition ? -this.offset.scroll.top : a ? 0 : this.offset.scroll.top),
                        left: l - this.offset.click.left - this.offset.relative.left - this.offset.parent.left + ("fixed" === this.cssPosition ? -this.offset.scroll.left : a ? 0 : this.offset.scroll.left),
                    }
                );
            },
            _clear: function () {
                this._removeClass(this.helper, "ui-draggable-dragging"),
                    this.helper[0] === this.element[0] || this.cancelHelperRemoval || this.helper.remove(),
                    (this.helper = null),
                    (this.cancelHelperRemoval = !1),
                    this.destroyOnClear && this.destroy();
            },
            _trigger: function (t, e, i) {
                return (
                    (i = i || this._uiHash()),
                    x.ui.plugin.call(this, t, [e, i, this], !0),
                    /^(drag|start|stop)/.test(t) && ((this.positionAbs = this._convertPositionTo("absolute")), (i.offset = this.positionAbs)),
                    x.Widget.prototype._trigger.call(this, t, e, i)
                );
            },
            plugins: {},
            _uiHash: function () {
                return { helper: this.helper, position: this.position, originalPosition: this.originalPosition, offset: this.positionAbs };
            },
        }),
            x.ui.plugin.add("draggable", "connectToSortable", {
                start: function (e, t, i) {
                    var n = x.extend({}, t, { item: i.element });
                    (i.sortables = []),
                        x(i.options.connectToSortable).each(function () {
                            var t = x(this).sortable("instance");
                            t && !t.options.disabled && (i.sortables.push(t), t.refreshPositions(), t._trigger("activate", e, n));
                        });
                },
                stop: function (e, t, i) {
                    var n = x.extend({}, t, { item: i.element });
                    (i.cancelHelperRemoval = !1),
                        x.each(i.sortables, function () {
                            var t = this;
                            t.isOver
                                ? ((t.isOver = 0),
                                  (i.cancelHelperRemoval = !0),
                                  (t.cancelHelperRemoval = !1),
                                  (t._storedCSS = { position: t.placeholder.css("position"), top: t.placeholder.css("top"), left: t.placeholder.css("left") }),
                                  t._mouseStop(e),
                                  (t.options.helper = t.options._helper))
                                : ((t.cancelHelperRemoval = !0), t._trigger("deactivate", e, n));
                        });
                },
                drag: function (i, n, s) {
                    x.each(s.sortables, function () {
                        var t = !1,
                            e = this;
                        (e.positionAbs = s.positionAbs),
                            (e.helperProportions = s.helperProportions),
                            (e.offset.click = s.offset.click),
                            e._intersectsWith(e.containerCache) &&
                                ((t = !0),
                                x.each(s.sortables, function () {
                                    return (
                                        (this.positionAbs = s.positionAbs),
                                        (this.helperProportions = s.helperProportions),
                                        (this.offset.click = s.offset.click),
                                        this !== e && this._intersectsWith(this.containerCache) && x.contains(e.element[0], this.element[0]) && (t = !1),
                                        t
                                    );
                                })),
                            t
                                ? (e.isOver ||
                                      ((e.isOver = 1),
                                      (s._parent = n.helper.parent()),
                                      (e.currentItem = n.helper.appendTo(e.element).data("ui-sortable-item", !0)),
                                      (e.options._helper = e.options.helper),
                                      (e.options.helper = function () {
                                          return n.helper[0];
                                      }),
                                      (i.target = e.currentItem[0]),
                                      e._mouseCapture(i, !0),
                                      e._mouseStart(i, !0, !0),
                                      (e.offset.click.top = s.offset.click.top),
                                      (e.offset.click.left = s.offset.click.left),
                                      (e.offset.parent.left -= s.offset.parent.left - e.offset.parent.left),
                                      (e.offset.parent.top -= s.offset.parent.top - e.offset.parent.top),
                                      s._trigger("toSortable", i),
                                      (s.dropped = e.element),
                                      x.each(s.sortables, function () {
                                          this.refreshPositions();
                                      }),
                                      (s.currentItem = s.element),
                                      (e.fromOutside = s)),
                                  e.currentItem && (e._mouseDrag(i), (n.position = e.position)))
                                : e.isOver &&
                                  ((e.isOver = 0),
                                  (e.cancelHelperRemoval = !0),
                                  (e.options._revert = e.options.revert),
                                  (e.options.revert = !1),
                                  e._trigger("out", i, e._uiHash(e)),
                                  e._mouseStop(i, !0),
                                  (e.options.revert = e.options._revert),
                                  (e.options.helper = e.options._helper),
                                  e.placeholder && e.placeholder.remove(),
                                  n.helper.appendTo(s._parent),
                                  s._refreshOffsets(i),
                                  (n.position = s._generatePosition(i, !0)),
                                  s._trigger("fromSortable", i),
                                  (s.dropped = !1),
                                  x.each(s.sortables, function () {
                                      this.refreshPositions();
                                  }));
                    });
                },
            }),
            x.ui.plugin.add("draggable", "cursor", {
                start: function (t, e, i) {
                    var n = x("body"),
                        s = i.options;
                    n.css("cursor") && (s._cursor = n.css("cursor")), n.css("cursor", s.cursor);
                },
                stop: function (t, e, i) {
                    var n = i.options;
                    n._cursor && x("body").css("cursor", n._cursor);
                },
            }),
            x.ui.plugin.add("draggable", "opacity", {
                start: function (t, e, i) {
                    var n = x(e.helper),
                        s = i.options;
                    n.css("opacity") && (s._opacity = n.css("opacity")), n.css("opacity", s.opacity);
                },
                stop: function (t, e, i) {
                    var n = i.options;
                    n._opacity && x(e.helper).css("opacity", n._opacity);
                },
            }),
            x.ui.plugin.add("draggable", "scroll", {
                start: function (t, e, i) {
                    i.scrollParentNotHidden || (i.scrollParentNotHidden = i.helper.scrollParent(!1)),
                        i.scrollParentNotHidden[0] !== i.document[0] && "HTML" !== i.scrollParentNotHidden[0].tagName && (i.overflowOffset = i.scrollParentNotHidden.offset());
                },
                drag: function (t, e, i) {
                    var n = i.options,
                        s = !1,
                        o = i.scrollParentNotHidden[0],
                        r = i.document[0];
                    o !== r && "HTML" !== o.tagName
                        ? ((n.axis && "x" === n.axis) ||
                              (i.overflowOffset.top + o.offsetHeight - t.pageY < n.scrollSensitivity
                                  ? (o.scrollTop = s = o.scrollTop + n.scrollSpeed)
                                  : t.pageY - i.overflowOffset.top < n.scrollSensitivity && (o.scrollTop = s = o.scrollTop - n.scrollSpeed)),
                          (n.axis && "y" === n.axis) ||
                              (i.overflowOffset.left + o.offsetWidth - t.pageX < n.scrollSensitivity
                                  ? (o.scrollLeft = s = o.scrollLeft + n.scrollSpeed)
                                  : t.pageX - i.overflowOffset.left < n.scrollSensitivity && (o.scrollLeft = s = o.scrollLeft - n.scrollSpeed)))
                        : ((n.axis && "x" === n.axis) ||
                              (t.pageY - x(r).scrollTop() < n.scrollSensitivity
                                  ? (s = x(r).scrollTop(x(r).scrollTop() - n.scrollSpeed))
                                  : x(window).height() - (t.pageY - x(r).scrollTop()) < n.scrollSensitivity && (s = x(r).scrollTop(x(r).scrollTop() + n.scrollSpeed))),
                          (n.axis && "y" === n.axis) ||
                              (t.pageX - x(r).scrollLeft() < n.scrollSensitivity
                                  ? (s = x(r).scrollLeft(x(r).scrollLeft() - n.scrollSpeed))
                                  : x(window).width() - (t.pageX - x(r).scrollLeft()) < n.scrollSensitivity && (s = x(r).scrollLeft(x(r).scrollLeft() + n.scrollSpeed)))),
                        !1 !== s && x.ui.ddmanager && !n.dropBehaviour && x.ui.ddmanager.prepareOffsets(i, t);
                },
            }),
            x.ui.plugin.add("draggable", "snap", {
                start: function (t, e, i) {
                    var n = i.options;
                    (i.snapElements = []),
                        x(n.snap.constructor !== String ? n.snap.items || ":data(ui-draggable)" : n.snap).each(function () {
                            var t = x(this),
                                e = t.offset();
                            this !== i.element[0] && i.snapElements.push({ item: this, width: t.outerWidth(), height: t.outerHeight(), top: e.top, left: e.left });
                        });
                },
                drag: function (t, e, i) {
                    var n,
                        s,
                        o,
                        r,
                        a,
                        l,
                        c,
                        h,
                        u,
                        d,
                        p = i.options,
                        f = p.snapTolerance,
                        g = e.offset.left,
                        m = g + i.helperProportions.width,
                        v = e.offset.top,
                        y = v + i.helperProportions.height;
                    for (u = i.snapElements.length - 1; 0 <= u; u--)
                        (l = (a = i.snapElements[u].left - i.margins.left) + i.snapElements[u].width),
                            (h = (c = i.snapElements[u].top - i.margins.top) + i.snapElements[u].height),
                            m < a - f || l + f < g || y < c - f || h + f < v || !x.contains(i.snapElements[u].item.ownerDocument, i.snapElements[u].item)
                                ? (i.snapElements[u].snapping && i.options.snap.release && i.options.snap.release.call(i.element, t, x.extend(i._uiHash(), { snapItem: i.snapElements[u].item })), (i.snapElements[u].snapping = !1))
                                : ("inner" !== p.snapMode &&
                                      ((n = Math.abs(c - y) <= f),
                                      (s = Math.abs(h - v) <= f),
                                      (o = Math.abs(a - m) <= f),
                                      (r = Math.abs(l - g) <= f),
                                      n && (e.position.top = i._convertPositionTo("relative", { top: c - i.helperProportions.height, left: 0 }).top),
                                      s && (e.position.top = i._convertPositionTo("relative", { top: h, left: 0 }).top),
                                      o && (e.position.left = i._convertPositionTo("relative", { top: 0, left: a - i.helperProportions.width }).left),
                                      r && (e.position.left = i._convertPositionTo("relative", { top: 0, left: l }).left)),
                                  (d = n || s || o || r),
                                  "outer" !== p.snapMode &&
                                      ((n = Math.abs(c - v) <= f),
                                      (s = Math.abs(h - y) <= f),
                                      (o = Math.abs(a - g) <= f),
                                      (r = Math.abs(l - m) <= f),
                                      n && (e.position.top = i._convertPositionTo("relative", { top: c, left: 0 }).top),
                                      s && (e.position.top = i._convertPositionTo("relative", { top: h - i.helperProportions.height, left: 0 }).top),
                                      o && (e.position.left = i._convertPositionTo("relative", { top: 0, left: a }).left),
                                      r && (e.position.left = i._convertPositionTo("relative", { top: 0, left: l - i.helperProportions.width }).left)),
                                  !i.snapElements[u].snapping && (n || s || o || r || d) && i.options.snap.snap && i.options.snap.snap.call(i.element, t, x.extend(i._uiHash(), { snapItem: i.snapElements[u].item })),
                                  (i.snapElements[u].snapping = n || s || o || r || d));
                },
            }),
            x.ui.plugin.add("draggable", "stack", {
                start: function (t, e, i) {
                    var n,
                        s = i.options,
                        o = x.makeArray(x(s.stack)).sort(function (t, e) {
                            return (parseInt(x(t).css("zIndex"), 10) || 0) - (parseInt(x(e).css("zIndex"), 10) || 0);
                        });
                    o.length &&
                        ((n = parseInt(x(o[0]).css("zIndex"), 10) || 0),
                        x(o).each(function (t) {
                            x(this).css("zIndex", n + t);
                        }),
                        this.css("zIndex", n + o.length));
                },
            }),
            x.ui.plugin.add("draggable", "zIndex", {
                start: function (t, e, i) {
                    var n = x(e.helper),
                        s = i.options;
                    n.css("zIndex") && (s._zIndex = n.css("zIndex")), n.css("zIndex", s.zIndex);
                },
                stop: function (t, e, i) {
                    var n = i.options;
                    n._zIndex && x(e.helper).css("zIndex", n._zIndex);
                },
            });
        x.ui.draggable;
        x.widget("ui.droppable", {
            version: "1.12.1",
            widgetEventPrefix: "drop",
            options: { accept: "*", addClasses: !0, greedy: !1, scope: "default", tolerance: "intersect", activate: null, deactivate: null, drop: null, out: null, over: null },
            _create: function () {
                var t,
                    e = this.options,
                    i = e.accept;
                (this.isover = !1),
                    (this.isout = !0),
                    (this.accept = x.isFunction(i)
                        ? i
                        : function (t) {
                              return t.is(i);
                          }),
                    (this.proportions = function () {
                        if (!arguments.length) return t || (t = { width: this.element[0].offsetWidth, height: this.element[0].offsetHeight });
                        t = arguments[0];
                    }),
                    this._addToManager(e.scope),
                    e.addClasses && this._addClass("ui-droppable");
            },
            _addToManager: function (t) {
                (x.ui.ddmanager.droppables[t] = x.ui.ddmanager.droppables[t] || []), x.ui.ddmanager.droppables[t].push(this);
            },
            _splice: function (t) {
                for (var e = 0; e < t.length; e++) t[e] === this && t.splice(e, 1);
            },
            _destroy: function () {
                var t = x.ui.ddmanager.droppables[this.options.scope];
                this._splice(t);
            },
            _setOption: function (t, e) {
                if ("accept" === t)
                    this.accept = x.isFunction(e)
                        ? e
                        : function (t) {
                              return t.is(e);
                          };
                else if ("scope" === t) {
                    var i = x.ui.ddmanager.droppables[this.options.scope];
                    this._splice(i), this._addToManager(e);
                }
                this._super(t, e);
            },
            _activate: function (t) {
                var e = x.ui.ddmanager.current;
                this._addActiveClass(), e && this._trigger("activate", t, this.ui(e));
            },
            _deactivate: function (t) {
                var e = x.ui.ddmanager.current;
                this._removeActiveClass(), e && this._trigger("deactivate", t, this.ui(e));
            },
            _over: function (t) {
                var e = x.ui.ddmanager.current;
                e && (e.currentItem || e.element)[0] !== this.element[0] && this.accept.call(this.element[0], e.currentItem || e.element) && (this._addHoverClass(), this._trigger("over", t, this.ui(e)));
            },
            _out: function (t) {
                var e = x.ui.ddmanager.current;
                e && (e.currentItem || e.element)[0] !== this.element[0] && this.accept.call(this.element[0], e.currentItem || e.element) && (this._removeHoverClass(), this._trigger("out", t, this.ui(e)));
            },
            _drop: function (e, t) {
                var i = t || x.ui.ddmanager.current,
                    n = !1;
                return (
                    !(!i || (i.currentItem || i.element)[0] === this.element[0]) &&
                    (this.element
                        .find(":data(ui-droppable)")
                        .not(".ui-draggable-dragging")
                        .each(function () {
                            var t = x(this).droppable("instance");
                            if (
                                t.options.greedy &&
                                !t.options.disabled &&
                                t.options.scope === i.options.scope &&
                                t.accept.call(t.element[0], i.currentItem || i.element) &&
                                p(i, x.extend(t, { offset: t.element.offset() }), t.options.tolerance, e)
                            )
                                return !(n = !0);
                        }),
                    !n && !!this.accept.call(this.element[0], i.currentItem || i.element) && (this._removeActiveClass(), this._removeHoverClass(), this._trigger("drop", e, this.ui(i)), this.element))
                );
            },
            ui: function (t) {
                return { draggable: t.currentItem || t.element, helper: t.helper, position: t.position, offset: t.positionAbs };
            },
            _addHoverClass: function () {
                this._addClass("ui-droppable-hover");
            },
            _removeHoverClass: function () {
                this._removeClass("ui-droppable-hover");
            },
            _addActiveClass: function () {
                this._addClass("ui-droppable-active");
            },
            _removeActiveClass: function () {
                this._removeClass("ui-droppable-active");
            },
        });
        var p = (x.ui.intersect = function (t, e, i, n) {
            if (!e.offset) return !1;
            var s = (t.positionAbs || t.position.absolute).left + t.margins.left,
                o = (t.positionAbs || t.position.absolute).top + t.margins.top,
                r = s + t.helperProportions.width,
                a = o + t.helperProportions.height,
                l = e.offset.left,
                c = e.offset.top,
                h = l + e.proportions().width,
                u = c + e.proportions().height;
            switch (i) {
                case "fit":
                    return l <= s && r <= h && c <= o && a <= u;
                case "intersect":
                    return l < s + t.helperProportions.width / 2 && r - t.helperProportions.width / 2 < h && c < o + t.helperProportions.height / 2 && a - t.helperProportions.height / 2 < u;
                case "pointer":
                    return f(n.pageY, c, e.proportions().height) && f(n.pageX, l, e.proportions().width);
                case "touch":
                    return ((c <= o && o <= u) || (c <= a && a <= u) || (o < c && u < a)) && ((l <= s && s <= h) || (l <= r && r <= h) || (s < l && h < r));
                default:
                    return !1;
            }
        });
        function f(t, e, i) {
            return e <= t && t < e + i;
        }
        !(x.ui.ddmanager = {
            current: null,
            droppables: { default: [] },
            prepareOffsets: function (t, e) {
                var i,
                    n,
                    s = x.ui.ddmanager.droppables[t.options.scope] || [],
                    o = e ? e.type : null,
                    r = (t.currentItem || t.element).find(":data(ui-droppable)").addBack();
                t: for (i = 0; i < s.length; i++)
                    if (!(s[i].options.disabled || (t && !s[i].accept.call(s[i].element[0], t.currentItem || t.element)))) {
                        for (n = 0; n < r.length; n++)
                            if (r[n] === s[i].element[0]) {
                                s[i].proportions().height = 0;
                                continue t;
                            }
                        (s[i].visible = "none" !== s[i].element.css("display")),
                            s[i].visible && ("mousedown" === o && s[i]._activate.call(s[i], e), (s[i].offset = s[i].element.offset()), s[i].proportions({ width: s[i].element[0].offsetWidth, height: s[i].element[0].offsetHeight }));
                    }
            },
            drop: function (t, e) {
                var i = !1;
                return (
                    x.each((x.ui.ddmanager.droppables[t.options.scope] || []).slice(), function () {
                        this.options &&
                            (!this.options.disabled && this.visible && p(t, this, this.options.tolerance, e) && (i = this._drop.call(this, e) || i),
                            !this.options.disabled && this.visible && this.accept.call(this.element[0], t.currentItem || t.element) && ((this.isout = !0), (this.isover = !1), this._deactivate.call(this, e)));
                    }),
                    i
                );
            },
            dragStart: function (t, e) {
                t.element.parentsUntil("body").on("scroll.droppable", function () {
                    t.options.refreshPositions || x.ui.ddmanager.prepareOffsets(t, e);
                });
            },
            drag: function (o, r) {
                o.options.refreshPositions && x.ui.ddmanager.prepareOffsets(o, r),
                    x.each(x.ui.ddmanager.droppables[o.options.scope] || [], function () {
                        if (!this.options.disabled && !this.greedyChild && this.visible) {
                            var t,
                                e,
                                i,
                                n = p(o, this, this.options.tolerance, r),
                                s = !n && this.isover ? "isout" : n && !this.isover ? "isover" : null;
                            s &&
                                (this.options.greedy &&
                                    ((e = this.options.scope),
                                    (i = this.element.parents(":data(ui-droppable)").filter(function () {
                                        return x(this).droppable("instance").options.scope === e;
                                    })).length && ((t = x(i[0]).droppable("instance")).greedyChild = "isover" === s)),
                                t && "isover" === s && ((t.isover = !1), (t.isout = !0), t._out.call(t, r)),
                                (this[s] = !0),
                                (this["isout" === s ? "isover" : "isout"] = !1),
                                this["isover" === s ? "_over" : "_out"].call(this, r),
                                t && "isout" === s && ((t.isout = !1), (t.isover = !0), t._over.call(t, r)));
                        }
                    });
            },
            dragStop: function (t, e) {
                t.element.parentsUntil("body").off("scroll.droppable"), t.options.refreshPositions || x.ui.ddmanager.prepareOffsets(t, e);
            },
        }) !== x.uiBackCompat &&
            x.widget("ui.droppable", x.ui.droppable, {
                options: { hoverClass: !1, activeClass: !1 },
                _addActiveClass: function () {
                    this._super(), this.options.activeClass && this.element.addClass(this.options.activeClass);
                },
                _removeActiveClass: function () {
                    this._super(), this.options.activeClass && this.element.removeClass(this.options.activeClass);
                },
                _addHoverClass: function () {
                    this._super(), this.options.hoverClass && this.element.addClass(this.options.hoverClass);
                },
                _removeHoverClass: function () {
                    this._super(), this.options.hoverClass && this.element.removeClass(this.options.hoverClass);
                },
            });
        x.ui.droppable;
        x.widget("ui.resizable", x.ui.mouse, {
            version: "1.12.1",
            widgetEventPrefix: "resize",
            options: {
                alsoResize: !1,
                animate: !1,
                animateDuration: "slow",
                animateEasing: "swing",
                aspectRatio: !1,
                autoHide: !1,
                classes: { "ui-resizable-se": "ui-icon ui-icon-gripsmall-diagonal-se" },
                containment: !1,
                ghost: !1,
                grid: !1,
                handles: "e,s,se",
                helper: !1,
                maxHeight: null,
                maxWidth: null,
                minHeight: 10,
                minWidth: 10,
                zIndex: 90,
                resize: null,
                start: null,
                stop: null,
            },
            _num: function (t) {
                return parseFloat(t) || 0;
            },
            _isNumber: function (t) {
                return !isNaN(parseFloat(t));
            },
            _hasScroll: function (t, e) {
                if ("hidden" === x(t).css("overflow")) return !1;
                var i,
                    n = e && "left" === e ? "scrollLeft" : "scrollTop";
                return 0 < t[n] || ((t[n] = 1), (i = 0 < t[n]), (t[n] = 0), i);
            },
            _create: function () {
                var t,
                    e = this.options,
                    i = this;
                this._addClass("ui-resizable"),
                    x.extend(this, {
                        _aspectRatio: !!e.aspectRatio,
                        aspectRatio: e.aspectRatio,
                        originalElement: this.element,
                        _proportionallyResizeElements: [],
                        _helper: e.helper || e.ghost || e.animate ? e.helper || "ui-resizable-helper" : null,
                    }),
                    this.element[0].nodeName.match(/^(canvas|textarea|input|select|button|img)$/i) &&
                        (this.element.wrap(
                            x("<div class='ui-wrapper' style='overflow: hidden;'></div>").css({
                                position: this.element.css("position"),
                                width: this.element.outerWidth(),
                                height: this.element.outerHeight(),
                                top: this.element.css("top"),
                                left: this.element.css("left"),
                            })
                        ),
                        (this.element = this.element.parent().data("ui-resizable", this.element.resizable("instance"))),
                        (this.elementIsWrapper = !0),
                        (t = {
                            marginTop: this.originalElement.css("marginTop"),
                            marginRight: this.originalElement.css("marginRight"),
                            marginBottom: this.originalElement.css("marginBottom"),
                            marginLeft: this.originalElement.css("marginLeft"),
                        }),
                        this.element.css(t),
                        this.originalElement.css("margin", 0),
                        (this.originalResizeStyle = this.originalElement.css("resize")),
                        this.originalElement.css("resize", "none"),
                        this._proportionallyResizeElements.push(this.originalElement.css({ position: "static", zoom: 1, display: "block" })),
                        this.originalElement.css(t),
                        this._proportionallyResize()),
                    this._setupHandles(),
                    e.autoHide &&
                        x(this.element)
                            .on("mouseenter", function () {
                                e.disabled || (i._removeClass("ui-resizable-autohide"), i._handles.show());
                            })
                            .on("mouseleave", function () {
                                e.disabled || i.resizing || (i._addClass("ui-resizable-autohide"), i._handles.hide());
                            }),
                    this._mouseInit();
            },
            _destroy: function () {
                this._mouseDestroy();
                function t(t) {
                    x(t).removeData("resizable").removeData("ui-resizable").off(".resizable").find(".ui-resizable-handle").remove();
                }
                var e;
                return (
                    this.elementIsWrapper &&
                        (t(this.element), (e = this.element), this.originalElement.css({ position: e.css("position"), width: e.outerWidth(), height: e.outerHeight(), top: e.css("top"), left: e.css("left") }).insertAfter(e), e.remove()),
                    this.originalElement.css("resize", this.originalResizeStyle),
                    t(this.originalElement),
                    this
                );
            },
            _setOption: function (t, e) {
                switch ((this._super(t, e), t)) {
                    case "handles":
                        this._removeHandles(), this._setupHandles();
                }
            },
            _setupHandles: function () {
                var t,
                    e,
                    i,
                    n,
                    s,
                    o = this.options,
                    r = this;
                if (
                    ((this.handles =
                        o.handles ||
                        (x(".ui-resizable-handle", this.element).length
                            ? { n: ".ui-resizable-n", e: ".ui-resizable-e", s: ".ui-resizable-s", w: ".ui-resizable-w", se: ".ui-resizable-se", sw: ".ui-resizable-sw", ne: ".ui-resizable-ne", nw: ".ui-resizable-nw" }
                            : "e,s,se")),
                    (this._handles = x()),
                    this.handles.constructor === String)
                )
                    for ("all" === this.handles && (this.handles = "n,e,s,w,se,sw,ne,nw"), i = this.handles.split(","), this.handles = {}, e = 0; e < i.length; e++)
                        (n = "ui-resizable-" + (t = x.trim(i[e]))), (s = x("<div>")), this._addClass(s, "ui-resizable-handle " + n), s.css({ zIndex: o.zIndex }), (this.handles[t] = ".ui-resizable-" + t), this.element.append(s);
                (this._renderAxis = function (t) {
                    var e, i, n, s;
                    for (e in ((t = t || this.element), this.handles))
                        this.handles[e].constructor === String
                            ? (this.handles[e] = this.element.children(this.handles[e]).first().show())
                            : (this.handles[e].jquery || this.handles[e].nodeType) && ((this.handles[e] = x(this.handles[e])), this._on(this.handles[e], { mousedown: r._mouseDown })),
                            this.elementIsWrapper &&
                                this.originalElement[0].nodeName.match(/^(textarea|input|select|button)$/i) &&
                                ((i = x(this.handles[e], this.element)),
                                (s = /sw|ne|nw|se|n|s/.test(e) ? i.outerHeight() : i.outerWidth()),
                                (n = ["padding", /ne|nw|n/.test(e) ? "Top" : /se|sw|s/.test(e) ? "Bottom" : /^e$/.test(e) ? "Right" : "Left"].join("")),
                                t.css(n, s),
                                this._proportionallyResize()),
                            (this._handles = this._handles.add(this.handles[e]));
                }),
                    this._renderAxis(this.element),
                    (this._handles = this._handles.add(this.element.find(".ui-resizable-handle"))),
                    this._handles.disableSelection(),
                    this._handles.on("mouseover", function () {
                        r.resizing || (this.className && (s = this.className.match(/ui-resizable-(se|sw|ne|nw|n|e|s|w)/i)), (r.axis = s && s[1] ? s[1] : "se"));
                    }),
                    o.autoHide && (this._handles.hide(), this._addClass("ui-resizable-autohide"));
            },
            _removeHandles: function () {
                this._handles.remove();
            },
            _mouseCapture: function (t) {
                var e,
                    i,
                    n = !1;
                for (e in this.handles) ((i = x(this.handles[e])[0]) !== t.target && !x.contains(i, t.target)) || (n = !0);
                return !this.options.disabled && n;
            },
            _mouseStart: function (t) {
                var e,
                    i,
                    n,
                    s = this.options,
                    o = this.element;
                return (
                    (this.resizing = !0),
                    this._renderProxy(),
                    (e = this._num(this.helper.css("left"))),
                    (i = this._num(this.helper.css("top"))),
                    s.containment && ((e += x(s.containment).scrollLeft() || 0), (i += x(s.containment).scrollTop() || 0)),
                    (this.offset = this.helper.offset()),
                    (this.position = { left: e, top: i }),
                    (this.size = this._helper ? { width: this.helper.width(), height: this.helper.height() } : { width: o.width(), height: o.height() }),
                    (this.originalSize = this._helper ? { width: o.outerWidth(), height: o.outerHeight() } : { width: o.width(), height: o.height() }),
                    (this.sizeDiff = { width: o.outerWidth() - o.width(), height: o.outerHeight() - o.height() }),
                    (this.originalPosition = { left: e, top: i }),
                    (this.originalMousePosition = { left: t.pageX, top: t.pageY }),
                    (this.aspectRatio = "number" === typeof s.aspectRatio ? s.aspectRatio : this.originalSize.width / this.originalSize.height || 1),
                    (n = x(".ui-resizable-" + this.axis).css("cursor")),
                    x("body").css("cursor", "auto" === n ? this.axis + "-resize" : n),
                    this._addClass("ui-resizable-resizing"),
                    this._propagate("start", t),
                    !0
                );
            },
            _mouseDrag: function (t) {
                var e,
                    i,
                    n = this.originalMousePosition,
                    s = this.axis,
                    o = t.pageX - n.left || 0,
                    r = t.pageY - n.top || 0,
                    a = this._change[s];
                return (
                    this._updatePrevProperties(),
                    a &&
                        ((e = a.apply(this, [t, o, r])),
                        this._updateVirtualBoundaries(t.shiftKey),
                        (this._aspectRatio || t.shiftKey) && (e = this._updateRatio(e, t)),
                        (e = this._respectSize(e, t)),
                        this._updateCache(e),
                        this._propagate("resize", t),
                        (i = this._applyChanges()),
                        !this._helper && this._proportionallyResizeElements.length && this._proportionallyResize(),
                        x.isEmptyObject(i) || (this._updatePrevProperties(), this._trigger("resize", t, this.ui()), this._applyChanges())),
                    !1
                );
            },
            _mouseStop: function (t) {
                this.resizing = !1;
                var e,
                    i,
                    n,
                    s,
                    o,
                    r,
                    a,
                    l = this.options,
                    c = this;
                return (
                    this._helper &&
                        ((n = (i = (e = this._proportionallyResizeElements).length && /textarea/i.test(e[0].nodeName)) && this._hasScroll(e[0], "left") ? 0 : c.sizeDiff.height),
                        (s = i ? 0 : c.sizeDiff.width),
                        (o = { width: c.helper.width() - s, height: c.helper.height() - n }),
                        (r = parseFloat(c.element.css("left")) + (c.position.left - c.originalPosition.left) || null),
                        (a = parseFloat(c.element.css("top")) + (c.position.top - c.originalPosition.top) || null),
                        l.animate || this.element.css(x.extend(o, { top: a, left: r })),
                        c.helper.height(c.size.height),
                        c.helper.width(c.size.width),
                        this._helper && !l.animate && this._proportionallyResize()),
                    x("body").css("cursor", "auto"),
                    this._removeClass("ui-resizable-resizing"),
                    this._propagate("stop", t),
                    this._helper && this.helper.remove(),
                    !1
                );
            },
            _updatePrevProperties: function () {
                (this.prevPosition = { top: this.position.top, left: this.position.left }), (this.prevSize = { width: this.size.width, height: this.size.height });
            },
            _applyChanges: function () {
                var t = {};
                return (
                    this.position.top !== this.prevPosition.top && (t.top = this.position.top + "px"),
                    this.position.left !== this.prevPosition.left && (t.left = this.position.left + "px"),
                    this.size.width !== this.prevSize.width && (t.width = this.size.width + "px"),
                    this.size.height !== this.prevSize.height && (t.height = this.size.height + "px"),
                    this.helper.css(t),
                    t
                );
            },
            _updateVirtualBoundaries: function (t) {
                var e,
                    i,
                    n,
                    s,
                    o,
                    r = this.options;
                (o = {
                    minWidth: this._isNumber(r.minWidth) ? r.minWidth : 0,
                    maxWidth: this._isNumber(r.maxWidth) ? r.maxWidth : 1 / 0,
                    minHeight: this._isNumber(r.minHeight) ? r.minHeight : 0,
                    maxHeight: this._isNumber(r.maxHeight) ? r.maxHeight : 1 / 0,
                }),
                    (this._aspectRatio || t) &&
                        ((e = o.minHeight * this.aspectRatio),
                        (n = o.minWidth / this.aspectRatio),
                        (i = o.maxHeight * this.aspectRatio),
                        (s = o.maxWidth / this.aspectRatio),
                        e > o.minWidth && (o.minWidth = e),
                        n > o.minHeight && (o.minHeight = n),
                        i < o.maxWidth && (o.maxWidth = i),
                        s < o.maxHeight && (o.maxHeight = s)),
                    (this._vBoundaries = o);
            },
            _updateCache: function (t) {
                (this.offset = this.helper.offset()),
                    this._isNumber(t.left) && (this.position.left = t.left),
                    this._isNumber(t.top) && (this.position.top = t.top),
                    this._isNumber(t.height) && (this.size.height = t.height),
                    this._isNumber(t.width) && (this.size.width = t.width);
            },
            _updateRatio: function (t) {
                var e = this.position,
                    i = this.size,
                    n = this.axis;
                return (
                    this._isNumber(t.height) ? (t.width = t.height * this.aspectRatio) : this._isNumber(t.width) && (t.height = t.width / this.aspectRatio),
                    "sw" === n && ((t.left = e.left + (i.width - t.width)), (t.top = null)),
                    "nw" === n && ((t.top = e.top + (i.height - t.height)), (t.left = e.left + (i.width - t.width))),
                    t
                );
            },
            _respectSize: function (t) {
                var e = this._vBoundaries,
                    i = this.axis,
                    n = this._isNumber(t.width) && e.maxWidth && e.maxWidth < t.width,
                    s = this._isNumber(t.height) && e.maxHeight && e.maxHeight < t.height,
                    o = this._isNumber(t.width) && e.minWidth && e.minWidth > t.width,
                    r = this._isNumber(t.height) && e.minHeight && e.minHeight > t.height,
                    a = this.originalPosition.left + this.originalSize.width,
                    l = this.originalPosition.top + this.originalSize.height,
                    c = /sw|nw|w/.test(i),
                    h = /nw|ne|n/.test(i);
                return (
                    o && (t.width = e.minWidth),
                    r && (t.height = e.minHeight),
                    n && (t.width = e.maxWidth),
                    s && (t.height = e.maxHeight),
                    o && c && (t.left = a - e.minWidth),
                    n && c && (t.left = a - e.maxWidth),
                    r && h && (t.top = l - e.minHeight),
                    s && h && (t.top = l - e.maxHeight),
                    t.width || t.height || t.left || !t.top ? t.width || t.height || t.top || !t.left || (t.left = null) : (t.top = null),
                    t
                );
            },
            _getPaddingPlusBorderDimensions: function (t) {
                for (
                    var e = 0,
                        i = [],
                        n = [t.css("borderTopWidth"), t.css("borderRightWidth"), t.css("borderBottomWidth"), t.css("borderLeftWidth")],
                        s = [t.css("paddingTop"), t.css("paddingRight"), t.css("paddingBottom"), t.css("paddingLeft")];
                    e < 4;
                    e++
                )
                    (i[e] = parseFloat(n[e]) || 0), (i[e] += parseFloat(s[e]) || 0);
                return { height: i[0] + i[2], width: i[1] + i[3] };
            },
            _proportionallyResize: function () {
                if (this._proportionallyResizeElements.length)
                    for (var t, e = 0, i = this.helper || this.element; e < this._proportionallyResizeElements.length; e++)
                        (t = this._proportionallyResizeElements[e]),
                            this.outerDimensions || (this.outerDimensions = this._getPaddingPlusBorderDimensions(t)),
                            t.css({ height: i.height() - this.outerDimensions.height || 0, width: i.width() - this.outerDimensions.width || 0 });
            },
            _renderProxy: function () {
                var t = this.element,
                    e = this.options;
                (this.elementOffset = t.offset()),
                    this._helper
                        ? ((this.helper = this.helper || x("<div style='overflow:hidden;'></div>")),
                          this._addClass(this.helper, this._helper),
                          this.helper.css({ width: this.element.outerWidth(), height: this.element.outerHeight(), position: "absolute", left: this.elementOffset.left + "px", top: this.elementOffset.top + "px", zIndex: ++e.zIndex }),
                          this.helper.appendTo("body").disableSelection())
                        : (this.helper = this.element);
            },
            _change: {
                e: function (t, e) {
                    return { width: this.originalSize.width + e };
                },
                w: function (t, e) {
                    var i = this.originalSize;
                    return { left: this.originalPosition.left + e, width: i.width - e };
                },
                n: function (t, e, i) {
                    var n = this.originalSize;
                    return { top: this.originalPosition.top + i, height: n.height - i };
                },
                s: function (t, e, i) {
                    return { height: this.originalSize.height + i };
                },
                se: function (t, e, i) {
                    return x.extend(this._change.s.apply(this, arguments), this._change.e.apply(this, [t, e, i]));
                },
                sw: function (t, e, i) {
                    return x.extend(this._change.s.apply(this, arguments), this._change.w.apply(this, [t, e, i]));
                },
                ne: function (t, e, i) {
                    return x.extend(this._change.n.apply(this, arguments), this._change.e.apply(this, [t, e, i]));
                },
                nw: function (t, e, i) {
                    return x.extend(this._change.n.apply(this, arguments), this._change.w.apply(this, [t, e, i]));
                },
            },
            _propagate: function (t, e) {
                x.ui.plugin.call(this, t, [e, this.ui()]), "resize" !== t && this._trigger(t, e, this.ui());
            },
            plugins: {},
            ui: function () {
                return { originalElement: this.originalElement, element: this.element, helper: this.helper, position: this.position, size: this.size, originalSize: this.originalSize, originalPosition: this.originalPosition };
            },
        }),
            x.ui.plugin.add("resizable", "animate", {
                stop: function (e) {
                    var i = x(this).resizable("instance"),
                        t = i.options,
                        n = i._proportionallyResizeElements,
                        s = n.length && /textarea/i.test(n[0].nodeName),
                        o = s && i._hasScroll(n[0], "left") ? 0 : i.sizeDiff.height,
                        r = s ? 0 : i.sizeDiff.width,
                        a = { width: i.size.width - r, height: i.size.height - o },
                        l = parseFloat(i.element.css("left")) + (i.position.left - i.originalPosition.left) || null,
                        c = parseFloat(i.element.css("top")) + (i.position.top - i.originalPosition.top) || null;
                    i.element.animate(x.extend(a, c && l ? { top: c, left: l } : {}), {
                        duration: t.animateDuration,
                        easing: t.animateEasing,
                        step: function () {
                            var t = { width: parseFloat(i.element.css("width")), height: parseFloat(i.element.css("height")), top: parseFloat(i.element.css("top")), left: parseFloat(i.element.css("left")) };
                            n && n.length && x(n[0]).css({ width: t.width, height: t.height }), i._updateCache(t), i._propagate("resize", e);
                        },
                    });
                },
            }),
            x.ui.plugin.add("resizable", "containment", {
                start: function () {
                    var i,
                        n,
                        t,
                        e,
                        s,
                        o,
                        r,
                        a = x(this).resizable("instance"),
                        l = a.options,
                        c = a.element,
                        h = l.containment,
                        u = h instanceof x ? h.get(0) : /parent/.test(h) ? c.parent().get(0) : h;
                    u &&
                        ((a.containerElement = x(u)),
                        /document/.test(h) || h === document
                            ? ((a.containerOffset = { left: 0, top: 0 }),
                              (a.containerPosition = { left: 0, top: 0 }),
                              (a.parentData = { element: x(document), left: 0, top: 0, width: x(document).width(), height: x(document).height() || document.body.parentNode.scrollHeight }))
                            : ((i = x(u)),
                              (n = []),
                              x(["Top", "Right", "Left", "Bottom"]).each(function (t, e) {
                                  n[t] = a._num(i.css("padding" + e));
                              }),
                              (a.containerOffset = i.offset()),
                              (a.containerPosition = i.position()),
                              (a.containerSize = { height: i.innerHeight() - n[3], width: i.innerWidth() - n[1] }),
                              (t = a.containerOffset),
                              (e = a.containerSize.height),
                              (s = a.containerSize.width),
                              (o = a._hasScroll(u, "left") ? u.scrollWidth : s),
                              (r = a._hasScroll(u) ? u.scrollHeight : e),
                              (a.parentData = { element: u, left: t.left, top: t.top, width: o, height: r })));
                },
                resize: function (t) {
                    var e,
                        i,
                        n,
                        s,
                        o = x(this).resizable("instance"),
                        r = o.options,
                        a = o.containerOffset,
                        l = o.position,
                        c = o._aspectRatio || t.shiftKey,
                        h = { top: 0, left: 0 },
                        u = o.containerElement,
                        d = !0;
                    u[0] !== document && /static/.test(u.css("position")) && (h = a),
                        l.left < (o._helper ? a.left : 0) &&
                            ((o.size.width = o.size.width + (o._helper ? o.position.left - a.left : o.position.left - h.left)), c && ((o.size.height = o.size.width / o.aspectRatio), (d = !1)), (o.position.left = r.helper ? a.left : 0)),
                        l.top < (o._helper ? a.top : 0) &&
                            ((o.size.height = o.size.height + (o._helper ? o.position.top - a.top : o.position.top)), c && ((o.size.width = o.size.height * o.aspectRatio), (d = !1)), (o.position.top = o._helper ? a.top : 0)),
                        (n = o.containerElement.get(0) === o.element.parent().get(0)),
                        (s = /relative|absolute/.test(o.containerElement.css("position"))),
                        n && s ? ((o.offset.left = o.parentData.left + o.position.left), (o.offset.top = o.parentData.top + o.position.top)) : ((o.offset.left = o.element.offset().left), (o.offset.top = o.element.offset().top)),
                        (e = Math.abs(o.sizeDiff.width + (o._helper ? o.offset.left - h.left : o.offset.left - a.left))),
                        (i = Math.abs(o.sizeDiff.height + (o._helper ? o.offset.top - h.top : o.offset.top - a.top))),
                        e + o.size.width >= o.parentData.width && ((o.size.width = o.parentData.width - e), c && ((o.size.height = o.size.width / o.aspectRatio), (d = !1))),
                        i + o.size.height >= o.parentData.height && ((o.size.height = o.parentData.height - i), c && ((o.size.width = o.size.height * o.aspectRatio), (d = !1))),
                        d || ((o.position.left = o.prevPosition.left), (o.position.top = o.prevPosition.top), (o.size.width = o.prevSize.width), (o.size.height = o.prevSize.height));
                },
                stop: function () {
                    var t = x(this).resizable("instance"),
                        e = t.options,
                        i = t.containerOffset,
                        n = t.containerPosition,
                        s = t.containerElement,
                        o = x(t.helper),
                        r = o.offset(),
                        a = o.outerWidth() - t.sizeDiff.width,
                        l = o.outerHeight() - t.sizeDiff.height;
                    t._helper && !e.animate && /relative/.test(s.css("position")) && x(this).css({ left: r.left - n.left - i.left, width: a, height: l }),
                        t._helper && !e.animate && /static/.test(s.css("position")) && x(this).css({ left: r.left - n.left - i.left, width: a, height: l });
                },
            }),
            x.ui.plugin.add("resizable", "alsoResize", {
                start: function () {
                    var t = x(this).resizable("instance").options;
                    x(t.alsoResize).each(function () {
                        var t = x(this);
                        t.data("ui-resizable-alsoresize", { width: parseFloat(t.width()), height: parseFloat(t.height()), left: parseFloat(t.css("left")), top: parseFloat(t.css("top")) });
                    });
                },
                resize: function (t, i) {
                    var e = x(this).resizable("instance"),
                        n = e.options,
                        s = e.originalSize,
                        o = e.originalPosition,
                        r = { height: e.size.height - s.height || 0, width: e.size.width - s.width || 0, top: e.position.top - o.top || 0, left: e.position.left - o.left || 0 };
                    x(n.alsoResize).each(function () {
                        var t = x(this),
                            n = x(this).data("ui-resizable-alsoresize"),
                            s = {},
                            e = t.parents(i.originalElement[0]).length ? ["width", "height"] : ["width", "height", "top", "left"];
                        x.each(e, function (t, e) {
                            var i = (n[e] || 0) + (r[e] || 0);
                            i && 0 <= i && (s[e] = i || null);
                        }),
                            t.css(s);
                    });
                },
                stop: function () {
                    x(this).removeData("ui-resizable-alsoresize");
                },
            }),
            x.ui.plugin.add("resizable", "ghost", {
                start: function () {
                    var t = x(this).resizable("instance"),
                        e = t.size;
                    (t.ghost = t.originalElement.clone()),
                        t.ghost.css({ opacity: 0.25, display: "block", position: "relative", height: e.height, width: e.width, margin: 0, left: 0, top: 0 }),
                        t._addClass(t.ghost, "ui-resizable-ghost"),
                        !1 !== x.uiBackCompat && "string" === typeof t.options.ghost && t.ghost.addClass(this.options.ghost),
                        t.ghost.appendTo(t.helper);
                },
                resize: function () {
                    var t = x(this).resizable("instance");
                    t.ghost && t.ghost.css({ position: "relative", height: t.size.height, width: t.size.width });
                },
                stop: function () {
                    var t = x(this).resizable("instance");
                    t.ghost && t.helper && t.helper.get(0).removeChild(t.ghost.get(0));
                },
            }),
            x.ui.plugin.add("resizable", "grid", {
                resize: function () {
                    var t,
                        e = x(this).resizable("instance"),
                        i = e.options,
                        n = e.size,
                        s = e.originalSize,
                        o = e.originalPosition,
                        r = e.axis,
                        a = "number" === typeof i.grid ? [i.grid, i.grid] : i.grid,
                        l = a[0] || 1,
                        c = a[1] || 1,
                        h = Math.round((n.width - s.width) / l) * l,
                        u = Math.round((n.height - s.height) / c) * c,
                        d = s.width + h,
                        p = s.height + u,
                        f = i.maxWidth && i.maxWidth < d,
                        g = i.maxHeight && i.maxHeight < p,
                        m = i.minWidth && i.minWidth > d,
                        v = i.minHeight && i.minHeight > p;
                    (i.grid = a),
                        m && (d += l),
                        v && (p += c),
                        f && (d -= l),
                        g && (p -= c),
                        /^(se|s|e)$/.test(r)
                            ? ((e.size.width = d), (e.size.height = p))
                            : /^(ne)$/.test(r)
                            ? ((e.size.width = d), (e.size.height = p), (e.position.top = o.top - u))
                            : /^(sw)$/.test(r)
                            ? ((e.size.width = d), (e.size.height = p), (e.position.left = o.left - h))
                            : ((p - c <= 0 || d - l <= 0) && (t = e._getPaddingPlusBorderDimensions(this)),
                              0 < p - c ? ((e.size.height = p), (e.position.top = o.top - u)) : ((p = c - t.height), (e.size.height = p), (e.position.top = o.top + s.height - p)),
                              0 < d - l ? ((e.size.width = d), (e.position.left = o.left - h)) : ((d = l - t.width), (e.size.width = d), (e.position.left = o.left + s.width - d)));
                },
            });
        x.ui.resizable,
            x.widget("ui.selectable", x.ui.mouse, {
                version: "1.12.1",
                options: { appendTo: "body", autoRefresh: !0, distance: 0, filter: "*", tolerance: "touch", selected: null, selecting: null, start: null, stop: null, unselected: null, unselecting: null },
                _create: function () {
                    var n = this;
                    this._addClass("ui-selectable"),
                        (this.dragged = !1),
                        (this.refresh = function () {
                            (n.elementPos = x(n.element[0]).offset()),
                                (n.selectees = x(n.options.filter, n.element[0])),
                                n._addClass(n.selectees, "ui-selectee"),
                                n.selectees.each(function () {
                                    var t = x(this),
                                        e = t.offset(),
                                        i = { left: e.left - n.elementPos.left, top: e.top - n.elementPos.top };
                                    x.data(this, "selectable-item", {
                                        element: this,
                                        $element: t,
                                        left: i.left,
                                        top: i.top,
                                        right: i.left + t.outerWidth(),
                                        bottom: i.top + t.outerHeight(),
                                        startselected: !1,
                                        selected: t.hasClass("ui-selected"),
                                        selecting: t.hasClass("ui-selecting"),
                                        unselecting: t.hasClass("ui-unselecting"),
                                    });
                                });
                        }),
                        this.refresh(),
                        this._mouseInit(),
                        (this.helper = x("<div>")),
                        this._addClass(this.helper, "ui-selectable-helper");
                },
                _destroy: function () {
                    this.selectees.removeData("selectable-item"), this._mouseDestroy();
                },
                _mouseStart: function (i) {
                    var n = this,
                        t = this.options;
                    (this.opos = [i.pageX, i.pageY]),
                        (this.elementPos = x(this.element[0]).offset()),
                        this.options.disabled ||
                            ((this.selectees = x(t.filter, this.element[0])),
                            this._trigger("start", i),
                            x(t.appendTo).append(this.helper),
                            this.helper.css({ left: i.pageX, top: i.pageY, width: 0, height: 0 }),
                            t.autoRefresh && this.refresh(),
                            this.selectees.filter(".ui-selected").each(function () {
                                var t = x.data(this, "selectable-item");
                                (t.startselected = !0),
                                    i.metaKey ||
                                        i.ctrlKey ||
                                        (n._removeClass(t.$element, "ui-selected"), (t.selected = !1), n._addClass(t.$element, "ui-unselecting"), (t.unselecting = !0), n._trigger("unselecting", i, { unselecting: t.element }));
                            }),
                            x(i.target)
                                .parents()
                                .addBack()
                                .each(function () {
                                    var t,
                                        e = x.data(this, "selectable-item");
                                    if (e)
                                        return (
                                            (t = (!i.metaKey && !i.ctrlKey) || !e.$element.hasClass("ui-selected")),
                                            n._removeClass(e.$element, t ? "ui-unselecting" : "ui-selected")._addClass(e.$element, t ? "ui-selecting" : "ui-unselecting"),
                                            (e.unselecting = !t),
                                            (e.selecting = t),
                                            (e.selected = t) ? n._trigger("selecting", i, { selecting: e.element }) : n._trigger("unselecting", i, { unselecting: e.element }),
                                            !1
                                        );
                                }));
                },
                _mouseDrag: function (n) {
                    if (((this.dragged = !0), !this.options.disabled)) {
                        var t,
                            s = this,
                            o = this.options,
                            r = this.opos[0],
                            a = this.opos[1],
                            l = n.pageX,
                            c = n.pageY;
                        return (
                            l < r && ((t = l), (l = r), (r = t)),
                            c < a && ((t = c), (c = a), (a = t)),
                            this.helper.css({ left: r, top: a, width: l - r, height: c - a }),
                            this.selectees.each(function () {
                                var t = x.data(this, "selectable-item"),
                                    e = !1,
                                    i = {};
                                t &&
                                    t.element !== s.element[0] &&
                                    ((i.left = t.left + s.elementPos.left),
                                    (i.right = t.right + s.elementPos.left),
                                    (i.top = t.top + s.elementPos.top),
                                    (i.bottom = t.bottom + s.elementPos.top),
                                    "touch" === o.tolerance ? (e = !(i.left > l || i.right < r || i.top > c || i.bottom < a)) : "fit" === o.tolerance && (e = i.left > r && i.right < l && i.top > a && i.bottom < c),
                                    e
                                        ? (t.selected && (s._removeClass(t.$element, "ui-selected"), (t.selected = !1)),
                                          t.unselecting && (s._removeClass(t.$element, "ui-unselecting"), (t.unselecting = !1)),
                                          t.selecting || (s._addClass(t.$element, "ui-selecting"), (t.selecting = !0), s._trigger("selecting", n, { selecting: t.element })))
                                        : (t.selecting &&
                                              ((n.metaKey || n.ctrlKey) && t.startselected
                                                  ? (s._removeClass(t.$element, "ui-selecting"), (t.selecting = !1), s._addClass(t.$element, "ui-selected"), (t.selected = !0))
                                                  : (s._removeClass(t.$element, "ui-selecting"),
                                                    (t.selecting = !1),
                                                    t.startselected && (s._addClass(t.$element, "ui-unselecting"), (t.unselecting = !0)),
                                                    s._trigger("unselecting", n, { unselecting: t.element }))),
                                          t.selected &&
                                              (n.metaKey ||
                                                  n.ctrlKey ||
                                                  t.startselected ||
                                                  (s._removeClass(t.$element, "ui-selected"), (t.selected = !1), s._addClass(t.$element, "ui-unselecting"), (t.unselecting = !0), s._trigger("unselecting", n, { unselecting: t.element })))));
                            }),
                            !1
                        );
                    }
                },
                _mouseStop: function (e) {
                    var i = this;
                    return (
                        (this.dragged = !1),
                        x(".ui-unselecting", this.element[0]).each(function () {
                            var t = x.data(this, "selectable-item");
                            i._removeClass(t.$element, "ui-unselecting"), (t.unselecting = !1), (t.startselected = !1), i._trigger("unselected", e, { unselected: t.element });
                        }),
                        x(".ui-selecting", this.element[0]).each(function () {
                            var t = x.data(this, "selectable-item");
                            i._removeClass(t.$element, "ui-selecting")._addClass(t.$element, "ui-selected"), (t.selecting = !1), (t.selected = !0), (t.startselected = !0), i._trigger("selected", e, { selected: t.element });
                        }),
                        this._trigger("stop", e),
                        this.helper.remove(),
                        !1
                    );
                },
            }),
            x.widget("ui.sortable", x.ui.mouse, {
                version: "1.12.1",
                widgetEventPrefix: "sort",
                ready: !1,
                options: {
                    appendTo: "parent",
                    axis: !1,
                    connectWith: !1,
                    containment: !1,
                    cursor: "auto",
                    cursorAt: !1,
                    dropOnEmpty: !0,
                    forcePlaceholderSize: !1,
                    forceHelperSize: !1,
                    grid: !1,
                    handle: !1,
                    helper: "original",
                    items: "> *",
                    opacity: !1,
                    placeholder: !1,
                    revert: !1,
                    scroll: !0,
                    scrollSensitivity: 20,
                    scrollSpeed: 20,
                    scope: "default",
                    tolerance: "intersect",
                    zIndex: 1e3,
                    activate: null,
                    beforeStop: null,
                    change: null,
                    deactivate: null,
                    out: null,
                    over: null,
                    receive: null,
                    remove: null,
                    sort: null,
                    start: null,
                    stop: null,
                    update: null,
                },
                _isOverAxis: function (t, e, i) {
                    return e <= t && t < e + i;
                },
                _isFloating: function (t) {
                    return /left|right/.test(t.css("float")) || /inline|table-cell/.test(t.css("display"));
                },
                _create: function () {
                    (this.containerCache = {}), this._addClass("ui-sortable"), this.refresh(), (this.offset = this.element.offset()), this._mouseInit(), this._setHandleClassName(), (this.ready = !0);
                },
                _setOption: function (t, e) {
                    this._super(t, e), "handle" === t && this._setHandleClassName();
                },
                _setHandleClassName: function () {
                    var t = this;
                    this._removeClass(this.element.find(".ui-sortable-handle"), "ui-sortable-handle"),
                        x.each(this.items, function () {
                            t._addClass(this.instance.options.handle ? this.item.find(this.instance.options.handle) : this.item, "ui-sortable-handle");
                        });
                },
                _destroy: function () {
                    this._mouseDestroy();
                    for (var t = this.items.length - 1; 0 <= t; t--) this.items[t].item.removeData(this.widgetName + "-item");
                    return this;
                },
                _mouseCapture: function (t, e) {
                    var i = null,
                        n = !1,
                        s = this;
                    return (
                        !this.reverting &&
                        !this.options.disabled &&
                        "static" !== this.options.type &&
                        (this._refreshItems(t),
                        x(t.target)
                            .parents()
                            .each(function () {
                                if (x.data(this, s.widgetName + "-item") === s) return (i = x(this)), !1;
                            }),
                        x.data(t.target, s.widgetName + "-item") === s && (i = x(t.target)),
                        !!i &&
                            !(
                                this.options.handle &&
                                !e &&
                                (x(this.options.handle, i)
                                    .find("*")
                                    .addBack()
                                    .each(function () {
                                        this === t.target && (n = !0);
                                    }),
                                !n)
                            ) &&
                            ((this.currentItem = i), this._removeCurrentsFromItems(), !0))
                    );
                },
                _mouseStart: function (t, e, i) {
                    var n,
                        s,
                        o = this.options;
                    if (
                        ((this.currentContainer = this).refreshPositions(),
                        (this.helper = this._createHelper(t)),
                        this._cacheHelperProportions(),
                        this._cacheMargins(),
                        (this.scrollParent = this.helper.scrollParent()),
                        (this.offset = this.currentItem.offset()),
                        (this.offset = { top: this.offset.top - this.margins.top, left: this.offset.left - this.margins.left }),
                        x.extend(this.offset, { click: { left: t.pageX - this.offset.left, top: t.pageY - this.offset.top }, parent: this._getParentOffset(), relative: this._getRelativeOffset() }),
                        this.helper.css("position", "absolute"),
                        (this.cssPosition = this.helper.css("position")),
                        (this.originalPosition = this._generatePosition(t)),
                        (this.originalPageX = t.pageX),
                        (this.originalPageY = t.pageY),
                        o.cursorAt && this._adjustOffsetFromHelper(o.cursorAt),
                        (this.domPosition = { prev: this.currentItem.prev()[0], parent: this.currentItem.parent()[0] }),
                        this.helper[0] !== this.currentItem[0] && this.currentItem.hide(),
                        this._createPlaceholder(),
                        o.containment && this._setContainment(),
                        o.cursor &&
                            "auto" !== o.cursor &&
                            ((s = this.document.find("body")), (this.storedCursor = s.css("cursor")), s.css("cursor", o.cursor), (this.storedStylesheet = x("<style>*{ cursor: " + o.cursor + " !important; }</style>").appendTo(s))),
                        o.opacity && (this.helper.css("opacity") && (this._storedOpacity = this.helper.css("opacity")), this.helper.css("opacity", o.opacity)),
                        o.zIndex && (this.helper.css("zIndex") && (this._storedZIndex = this.helper.css("zIndex")), this.helper.css("zIndex", o.zIndex)),
                        this.scrollParent[0] !== this.document[0] && "HTML" !== this.scrollParent[0].tagName && (this.overflowOffset = this.scrollParent.offset()),
                        this._trigger("start", t, this._uiHash()),
                        this._preserveHelperProportions || this._cacheHelperProportions(),
                        !i)
                    )
                        for (n = this.containers.length - 1; 0 <= n; n--) this.containers[n]._trigger("activate", t, this._uiHash(this));
                    return (
                        x.ui.ddmanager && (x.ui.ddmanager.current = this),
                        x.ui.ddmanager && !o.dropBehaviour && x.ui.ddmanager.prepareOffsets(this, t),
                        (this.dragging = !0),
                        this._addClass(this.helper, "ui-sortable-helper"),
                        this._mouseDrag(t),
                        !0
                    );
                },
                _mouseDrag: function (t) {
                    var e,
                        i,
                        n,
                        s,
                        o = this.options,
                        r = !1;
                    for (
                        this.position = this._generatePosition(t),
                            this.positionAbs = this._convertPositionTo("absolute"),
                            this.lastPositionAbs || (this.lastPositionAbs = this.positionAbs),
                            this.options.scroll &&
                                (this.scrollParent[0] !== this.document[0] && "HTML" !== this.scrollParent[0].tagName
                                    ? (this.overflowOffset.top + this.scrollParent[0].offsetHeight - t.pageY < o.scrollSensitivity
                                          ? (this.scrollParent[0].scrollTop = r = this.scrollParent[0].scrollTop + o.scrollSpeed)
                                          : t.pageY - this.overflowOffset.top < o.scrollSensitivity && (this.scrollParent[0].scrollTop = r = this.scrollParent[0].scrollTop - o.scrollSpeed),
                                      this.overflowOffset.left + this.scrollParent[0].offsetWidth - t.pageX < o.scrollSensitivity
                                          ? (this.scrollParent[0].scrollLeft = r = this.scrollParent[0].scrollLeft + o.scrollSpeed)
                                          : t.pageX - this.overflowOffset.left < o.scrollSensitivity && (this.scrollParent[0].scrollLeft = r = this.scrollParent[0].scrollLeft - o.scrollSpeed))
                                    : (t.pageY - this.document.scrollTop() < o.scrollSensitivity
                                          ? (r = this.document.scrollTop(this.document.scrollTop() - o.scrollSpeed))
                                          : this.window.height() - (t.pageY - this.document.scrollTop()) < o.scrollSensitivity && (r = this.document.scrollTop(this.document.scrollTop() + o.scrollSpeed)),
                                      t.pageX - this.document.scrollLeft() < o.scrollSensitivity
                                          ? (r = this.document.scrollLeft(this.document.scrollLeft() - o.scrollSpeed))
                                          : this.window.width() - (t.pageX - this.document.scrollLeft()) < o.scrollSensitivity && (r = this.document.scrollLeft(this.document.scrollLeft() + o.scrollSpeed))),
                                !1 !== r && x.ui.ddmanager && !o.dropBehaviour && x.ui.ddmanager.prepareOffsets(this, t)),
                            this.positionAbs = this._convertPositionTo("absolute"),
                            (this.options.axis && "y" === this.options.axis) || (this.helper[0].style.left = this.position.left + "px"),
                            (this.options.axis && "x" === this.options.axis) || (this.helper[0].style.top = this.position.top + "px"),
                            e = this.items.length - 1;
                        0 <= e;
                        e--
                    )
                        if (
                            ((n = (i = this.items[e]).item[0]),
                            (s = this._intersectsWithPointer(i)) &&
                                i.instance === this.currentContainer &&
                                !(n === this.currentItem[0] || this.placeholder[1 === s ? "next" : "prev"]()[0] === n || x.contains(this.placeholder[0], n) || ("semi-dynamic" === this.options.type && x.contains(this.element[0], n))))
                        ) {
                            if (((this.direction = 1 === s ? "down" : "up"), "pointer" !== this.options.tolerance && !this._intersectsWithSides(i))) break;
                            this._rearrange(t, i), this._trigger("change", t, this._uiHash());
                            break;
                        }
                    return this._contactContainers(t), x.ui.ddmanager && x.ui.ddmanager.drag(this, t), this._trigger("sort", t, this._uiHash()), (this.lastPositionAbs = this.positionAbs), !1;
                },
                _mouseStop: function (t, e) {
                    if (t) {
                        if ((x.ui.ddmanager && !this.options.dropBehaviour && x.ui.ddmanager.drop(this, t), this.options.revert)) {
                            var i = this,
                                n = this.placeholder.offset(),
                                s = this.options.axis,
                                o = {};
                            (s && "x" !== s) || (o.left = n.left - this.offset.parent.left - this.margins.left + (this.offsetParent[0] === this.document[0].body ? 0 : this.offsetParent[0].scrollLeft)),
                                (s && "y" !== s) || (o.top = n.top - this.offset.parent.top - this.margins.top + (this.offsetParent[0] === this.document[0].body ? 0 : this.offsetParent[0].scrollTop)),
                                (this.reverting = !0),
                                x(this.helper).animate(o, parseInt(this.options.revert, 10) || 500, function () {
                                    i._clear(t);
                                });
                        } else this._clear(t, e);
                        return !1;
                    }
                },
                cancel: function () {
                    if (this.dragging) {
                        this._mouseUp(new x.Event("mouseup", { target: null })),
                            "original" === this.options.helper ? (this.currentItem.css(this._storedCSS), this._removeClass(this.currentItem, "ui-sortable-helper")) : this.currentItem.show();
                        for (var t = this.containers.length - 1; 0 <= t; t--)
                            this.containers[t]._trigger("deactivate", null, this._uiHash(this)),
                                this.containers[t].containerCache.over && (this.containers[t]._trigger("out", null, this._uiHash(this)), (this.containers[t].containerCache.over = 0));
                    }
                    return (
                        this.placeholder &&
                            (this.placeholder[0].parentNode && this.placeholder[0].parentNode.removeChild(this.placeholder[0]),
                            "original" !== this.options.helper && this.helper && this.helper[0].parentNode && this.helper.remove(),
                            x.extend(this, { helper: null, dragging: !1, reverting: !1, _noFinalSort: null }),
                            this.domPosition.prev ? x(this.domPosition.prev).after(this.currentItem) : x(this.domPosition.parent).prepend(this.currentItem)),
                        this
                    );
                },
                serialize: function (e) {
                    var t = this._getItemsAsjQuery(e && e.connected),
                        i = [];
                    return (
                        (e = e || {}),
                        x(t).each(function () {
                            var t = (x(e.item || this).attr(e.attribute || "id") || "").match(e.expression || /(.+)[\-=_](.+)/);
                            t && i.push((e.key || t[1] + "[]") + "=" + (e.key && e.expression ? t[1] : t[2]));
                        }),
                        !i.length && e.key && i.push(e.key + "="),
                        i.join("&")
                    );
                },
                toArray: function (t) {
                    var e = this._getItemsAsjQuery(t && t.connected),
                        i = [];
                    return (
                        (t = t || {}),
                        e.each(function () {
                            i.push(x(t.item || this).attr(t.attribute || "id") || "");
                        }),
                        i
                    );
                },
                _intersectsWith: function (t) {
                    var e = this.positionAbs.left,
                        i = e + this.helperProportions.width,
                        n = this.positionAbs.top,
                        s = n + this.helperProportions.height,
                        o = t.left,
                        r = o + t.width,
                        a = t.top,
                        l = a + t.height,
                        c = this.offset.click.top,
                        h = this.offset.click.left,
                        u = "x" === this.options.axis || (a < n + c && n + c < l),
                        d = "y" === this.options.axis || (o < e + h && e + h < r),
                        p = u && d;
                    return "pointer" === this.options.tolerance ||
                        this.options.forcePointerForContainers ||
                        ("pointer" !== this.options.tolerance && this.helperProportions[this.floating ? "width" : "height"] > t[this.floating ? "width" : "height"])
                        ? p
                        : o < e + this.helperProportions.width / 2 && i - this.helperProportions.width / 2 < r && a < n + this.helperProportions.height / 2 && s - this.helperProportions.height / 2 < l;
                },
                _intersectsWithPointer: function (t) {
                    var e,
                        i,
                        n = "x" === this.options.axis || this._isOverAxis(this.positionAbs.top + this.offset.click.top, t.top, t.height),
                        s = "y" === this.options.axis || this._isOverAxis(this.positionAbs.left + this.offset.click.left, t.left, t.width);
                    return !(!n || !s) && ((e = this._getDragVerticalDirection()), (i = this._getDragHorizontalDirection()), this.floating ? ("right" === i || "down" === e ? 2 : 1) : e && ("down" === e ? 2 : 1));
                },
                _intersectsWithSides: function (t) {
                    var e = this._isOverAxis(this.positionAbs.top + this.offset.click.top, t.top + t.height / 2, t.height),
                        i = this._isOverAxis(this.positionAbs.left + this.offset.click.left, t.left + t.width / 2, t.width),
                        n = this._getDragVerticalDirection(),
                        s = this._getDragHorizontalDirection();
                    return this.floating && s ? ("right" === s && i) || ("left" === s && !i) : n && (("down" === n && e) || ("up" === n && !e));
                },
                _getDragVerticalDirection: function () {
                    var t = this.positionAbs.top - this.lastPositionAbs.top;
                    return 0 !==    t && (0 < t ? "down" : "up");
                },
                _getDragHorizontalDirection: function () {
                    var t = this.positionAbs.left - this.lastPositionAbs.left;
                    return 0 !==    t && (0 < t ? "right" : "left");
                },
                refresh: function (t) {
                    return this._refreshItems(t), this._setHandleClassName(), this.refreshPositions(), this;
                },
                _connectWith: function () {
                    var t = this.options;
                    return t.connectWith.constructor === String ? [t.connectWith] : t.connectWith;
                },
                _getItemsAsjQuery: function (t) {
                    var e,
                        i,
                        n,
                        s,
                        o = [],
                        r = [],
                        a = this._connectWith();
                    if (a && t)
                        for (e = a.length - 1; 0 <= e; e--)
                            for (i = (n = x(a[e], this.document[0])).length - 1; 0 <= i; i--)
                                (s = x.data(n[i], this.widgetFullName)) &&
                                    s !== this &&
                                    !s.options.disabled &&
                                    r.push([x.isFunction(s.options.items) ? s.options.items.call(s.element) : x(s.options.items, s.element).not(".ui-sortable-helper").not(".ui-sortable-placeholder"), s]);
                    function l() {
                        o.push(this);
                    }
                    for (
                        r.push([
                            x.isFunction(this.options.items)
                                ? this.options.items.call(this.element, null, { options: this.options, item: this.currentItem })
                                : x(this.options.items, this.element).not(".ui-sortable-helper").not(".ui-sortable-placeholder"),
                            this,
                        ]),
                            e = r.length - 1;
                        0 <= e;
                        e--
                    )
                        r[e][0].each(l);
                    return x(o);
                },
                _removeCurrentsFromItems: function () {
                    var i = this.currentItem.find(":data(" + this.widgetName + "-item)");
                    this.items = x.grep(this.items, function (t) {
                        for (var e = 0; e < i.length; e++) if (i[e] === t.item[0]) return !1;
                        return !0;
                    });
                },
                _refreshItems: function (t) {
                    (this.items = []), (this.containers = [this]);
                    var e,
                        i,
                        n,
                        s,
                        o,
                        r,
                        a,
                        l,
                        c = this.items,
                        h = [[x.isFunction(this.options.items) ? this.options.items.call(this.element[0], t, { item: this.currentItem }) : x(this.options.items, this.element), this]],
                        u = this._connectWith();
                    if (u && this.ready)
                        for (e = u.length - 1; 0 <= e; e--)
                            for (i = (n = x(u[e], this.document[0])).length - 1; 0 <= i; i--)
                                (s = x.data(n[i], this.widgetFullName)) &&
                                    s !== this &&
                                    !s.options.disabled &&
                                    (h.push([x.isFunction(s.options.items) ? s.options.items.call(s.element[0], t, { item: this.currentItem }) : x(s.options.items, s.element), s]), this.containers.push(s));
                    for (e = h.length - 1; 0 <= e; e--) for (o = h[e][1], i = 0, l = (r = h[e][0]).length; i < l; i++) (a = x(r[i])).data(this.widgetName + "-item", o), c.push({ item: a, instance: o, width: 0, height: 0, left: 0, top: 0 });
                },
                refreshPositions: function (t) {
                    var e, i, n, s;
                    for (
                        this.floating = !!this.items.length && ("x" === this.options.axis || this._isFloating(this.items[0].item)),
                            this.offsetParent && this.helper && (this.offset.parent = this._getParentOffset()),
                            e = this.items.length - 1;
                        0 <= e;
                        e--
                    )
                        ((i = this.items[e]).instance !== this.currentContainer && this.currentContainer && i.item[0] !== this.currentItem[0]) ||
                            ((n = this.options.toleranceElement ? x(this.options.toleranceElement, i.item) : i.item), t || ((i.width = n.outerWidth()), (i.height = n.outerHeight())), (s = n.offset()), (i.left = s.left), (i.top = s.top));
                    if (this.options.custom && this.options.custom.refreshContainers) this.options.custom.refreshContainers.call(this);
                    else
                        for (e = this.containers.length - 1; 0 <= e; e--)
                            (s = this.containers[e].element.offset()),
                                (this.containers[e].containerCache.left = s.left),
                                (this.containers[e].containerCache.top = s.top),
                                (this.containers[e].containerCache.width = this.containers[e].element.outerWidth()),
                                (this.containers[e].containerCache.height = this.containers[e].element.outerHeight());
                    return this;
                },
                _createPlaceholder: function (i) {
                    var n,
                        s = (i = i || this).options;
                    (s.placeholder && s.placeholder.constructor !== String) ||
                        ((n = s.placeholder),
                        (s.placeholder = {
                            element: function () {
                                var t = i.currentItem[0].nodeName.toLowerCase(),
                                    e = x("<" + t + ">", i.document[0]);
                                return (
                                    i._addClass(e, "ui-sortable-placeholder", n || i.currentItem[0].className)._removeClass(e, "ui-sortable-helper"),
                                    "tbody" === t
                                        ? i._createTrPlaceholder(i.currentItem.find("tr").eq(0), x("<tr>", i.document[0]).appendTo(e))
                                        : "tr" === t
                                        ? i._createTrPlaceholder(i.currentItem, e)
                                        : "img" === t && e.attr("src", i.currentItem.attr("src")),
                                    n || e.css("visibility", "hidden"),
                                    e
                                );
                            },
                            update: function (t, e) {
                                (n && !s.forcePlaceholderSize) ||
                                    (e.height() || e.height(i.currentItem.innerHeight() - parseInt(i.currentItem.css("paddingTop") || 0, 10) - parseInt(i.currentItem.css("paddingBottom") || 0, 10)),
                                    e.width() || e.width(i.currentItem.innerWidth() - parseInt(i.currentItem.css("paddingLeft") || 0, 10) - parseInt(i.currentItem.css("paddingRight") || 0, 10)));
                            },
                        })),
                        (i.placeholder = x(s.placeholder.element.call(i.element, i.currentItem))),
                        i.currentItem.after(i.placeholder),
                        s.placeholder.update(i, i.placeholder);
                },
                _createTrPlaceholder: function (t, e) {
                    var i = this;
                    t.children().each(function () {
                        x("<td>&#160;</td>", i.document[0])
                            .attr("colspan", x(this).attr("colspan") || 1)
                            .appendTo(e);
                    });
                },
                _contactContainers: function (t) {
                    var e,
                        i,
                        n,
                        s,
                        o,
                        r,
                        a,
                        l,
                        c,
                        h,
                        u = null,
                        d = null;
                    for (e = this.containers.length - 1; 0 <= e; e--)
                        if (!x.contains(this.currentItem[0], this.containers[e].element[0]))
                            if (this._intersectsWith(this.containers[e].containerCache)) {
                                if (u && x.contains(this.containers[e].element[0], u.element[0])) continue;
                                (u = this.containers[e]), (d = e);
                            } else this.containers[e].containerCache.over && (this.containers[e]._trigger("out", t, this._uiHash(this)), (this.containers[e].containerCache.over = 0));
                    if (u)
                        if (1 === this.containers.length) this.containers[d].containerCache.over || (this.containers[d]._trigger("over", t, this._uiHash(this)), (this.containers[d].containerCache.over = 1));
                        else {
                            for (n = 1e4, s = null, o = (c = u.floating || this._isFloating(this.currentItem)) ? "left" : "top", r = c ? "width" : "height", h = c ? "pageX" : "pageY", i = this.items.length - 1; 0 <= i; i--)
                                x.contains(this.containers[d].element[0], this.items[i].item[0]) &&
                                    this.items[i].item[0] !== this.currentItem[0] &&
                                    ((a = this.items[i].item.offset()[o]),
                                    (l = !1),
                                    t[h] - a > this.items[i][r] / 2 && (l = !0),
                                    Math.abs(t[h] - a) < n && ((n = Math.abs(t[h] - a)), (s = this.items[i]), (this.direction = l ? "up" : "down")));
                            if (!s && !this.options.dropOnEmpty) return;
                            if (this.currentContainer === this.containers[d])
                                return void (this.currentContainer.containerCache.over || (this.containers[d]._trigger("over", t, this._uiHash()), (this.currentContainer.containerCache.over = 1)));
                            s ? this._rearrange(t, s, null, !0) : this._rearrange(t, null, this.containers[d].element, !0),
                                this._trigger("change", t, this._uiHash()),
                                this.containers[d]._trigger("change", t, this._uiHash(this)),
                                (this.currentContainer = this.containers[d]),
                                this.options.placeholder.update(this.currentContainer, this.placeholder),
                                this.containers[d]._trigger("over", t, this._uiHash(this)),
                                (this.containers[d].containerCache.over = 1);
                        }
                },
                _createHelper: function (t) {
                    var e = this.options,
                        i = x.isFunction(e.helper) ? x(e.helper.apply(this.element[0], [t, this.currentItem])) : "clone" === e.helper ? this.currentItem.clone() : this.currentItem;
                    return (
                        i.parents("body").length || x("parent" !== e.appendTo ? e.appendTo : this.currentItem[0].parentNode)[0].appendChild(i[0]),
                        i[0] === this.currentItem[0] &&
                            (this._storedCSS = {
                                width: this.currentItem[0].style.width,
                                height: this.currentItem[0].style.height,
                                position: this.currentItem.css("position"),
                                top: this.currentItem.css("top"),
                                left: this.currentItem.css("left"),
                            }),
                        (i[0].style.width && !e.forceHelperSize) || i.width(this.currentItem.width()),
                        (i[0].style.height && !e.forceHelperSize) || i.height(this.currentItem.height()),
                        i
                    );
                },
                _adjustOffsetFromHelper: function (t) {
                    "string" === typeof t && (t = t.split(" ")),
                        x.isArray(t) && (t = { left: +t[0], top: +t[1] || 0 }),
                        "left" in t && (this.offset.click.left = t.left + this.margins.left),
                        "right" in t && (this.offset.click.left = this.helperProportions.width - t.right + this.margins.left),
                        "top" in t && (this.offset.click.top = t.top + this.margins.top),
                        "bottom" in t && (this.offset.click.top = this.helperProportions.height - t.bottom + this.margins.top);
                },
                _getParentOffset: function () {
                    this.offsetParent = this.helper.offsetParent();
                    var t = this.offsetParent.offset();
                    return (
                        "absolute" === this.cssPosition &&
                            this.scrollParent[0] !== this.document[0] &&
                            x.contains(this.scrollParent[0], this.offsetParent[0]) &&
                            ((t.left += this.scrollParent.scrollLeft()), (t.top += this.scrollParent.scrollTop())),
                        (this.offsetParent[0] === this.document[0].body || (this.offsetParent[0].tagName && "html" === this.offsetParent[0].tagName.toLowerCase() && x.ui.ie)) && (t = { top: 0, left: 0 }),
                        { top: t.top + (parseInt(this.offsetParent.css("borderTopWidth"), 10) || 0), left: t.left + (parseInt(this.offsetParent.css("borderLeftWidth"), 10) || 0) }
                    );
                },
                _getRelativeOffset: function () {
                    if ("relative" !== this.cssPosition) return { top: 0, left: 0 };
                    var t = this.currentItem.position();
                    return { top: t.top - (parseInt(this.helper.css("top"), 10) || 0) + this.scrollParent.scrollTop(), left: t.left - (parseInt(this.helper.css("left"), 10) || 0) + this.scrollParent.scrollLeft() };
                },
                _cacheMargins: function () {
                    this.margins = { left: parseInt(this.currentItem.css("marginLeft"), 10) || 0, top: parseInt(this.currentItem.css("marginTop"), 10) || 0 };
                },
                _cacheHelperProportions: function () {
                    this.helperProportions = { width: this.helper.outerWidth(), height: this.helper.outerHeight() };
                },
                _setContainment: function () {
                    var t,
                        e,
                        i,
                        n = this.options;
                    "parent" === n.containment && (n.containment = this.helper[0].parentNode),
                        ("document" !== n.containment && "window" !== n.containment) ||
                            (this.containment = [
                                0 - this.offset.relative.left - this.offset.parent.left,
                                0 - this.offset.relative.top - this.offset.parent.top,
                                "document" === n.containment ? this.document.width() : this.window.width() - this.helperProportions.width - this.margins.left,
                                ("document" === n.containment ? this.document.height() || document.body.parentNode.scrollHeight : this.window.height() || this.document[0].body.parentNode.scrollHeight) -
                                    this.helperProportions.height -
                                    this.margins.top,
                            ]),
                        /^(document|window|parent)$/.test(n.containment) ||
                            ((t = x(n.containment)[0]),
                            (e = x(n.containment).offset()),
                            (i = "hidden" !== x(t).css("overflow")),
                            (this.containment = [
                                e.left + (parseInt(x(t).css("borderLeftWidth"), 10) || 0) + (parseInt(x(t).css("paddingLeft"), 10) || 0) - this.margins.left,
                                e.top + (parseInt(x(t).css("borderTopWidth"), 10) || 0) + (parseInt(x(t).css("paddingTop"), 10) || 0) - this.margins.top,
                                e.left +
                                    (i ? Math.max(t.scrollWidth, t.offsetWidth) : t.offsetWidth) -
                                    (parseInt(x(t).css("borderLeftWidth"), 10) || 0) -
                                    (parseInt(x(t).css("paddingRight"), 10) || 0) -
                                    this.helperProportions.width -
                                    this.margins.left,
                                e.top +
                                    (i ? Math.max(t.scrollHeight, t.offsetHeight) : t.offsetHeight) -
                                    (parseInt(x(t).css("borderTopWidth"), 10) || 0) -
                                    (parseInt(x(t).css("paddingBottom"), 10) || 0) -
                                    this.helperProportions.height -
                                    this.margins.top,
                            ]));
                },
                _convertPositionTo: function (t, e) {
                    e = e || this.position;
                    var i = "absolute" === t ? 1 : -1,
                        n = "absolute" !== this.cssPosition || (this.scrollParent[0] !== this.document[0] && x.contains(this.scrollParent[0], this.offsetParent[0])) ? this.scrollParent : this.offsetParent,
                        s = /(html|body)/i.test(n[0].tagName);
                    return {
                        top: e.top + this.offset.relative.top * i + this.offset.parent.top * i - ("fixed" === this.cssPosition ? -this.scrollParent.scrollTop() : s ? 0 : n.scrollTop()) * i,
                        left: e.left + this.offset.relative.left * i + this.offset.parent.left * i - ("fixed" === this.cssPosition ? -this.scrollParent.scrollLeft() : s ? 0 : n.scrollLeft()) * i,
                    };
                },
                _generatePosition: function (t) {
                    var e,
                        i,
                        n = this.options,
                        s = t.pageX,
                        o = t.pageY,
                        r = "absolute" !== this.cssPosition || (this.scrollParent[0] !== this.document[0] && x.contains(this.scrollParent[0], this.offsetParent[0])) ? this.scrollParent : this.offsetParent,
                        a = /(html|body)/i.test(r[0].tagName);
                    return (
                        "relative" !== this.cssPosition || (this.scrollParent[0] !== this.document[0] && this.scrollParent[0] !== this.offsetParent[0]) || (this.offset.relative = this._getRelativeOffset()),
                        this.originalPosition &&
                            (this.containment &&
                                (t.pageX - this.offset.click.left < this.containment[0] && (s = this.containment[0] + this.offset.click.left),
                                t.pageY - this.offset.click.top < this.containment[1] && (o = this.containment[1] + this.offset.click.top),
                                t.pageX - this.offset.click.left > this.containment[2] && (s = this.containment[2] + this.offset.click.left),
                                t.pageY - this.offset.click.top > this.containment[3] && (o = this.containment[3] + this.offset.click.top)),
                            n.grid &&
                                ((e = this.originalPageY + Math.round((o - this.originalPageY) / n.grid[1]) * n.grid[1]),
                                (o =
                                    !this.containment || (e - this.offset.click.top >= this.containment[1] && e - this.offset.click.top <= this.containment[3])
                                        ? e
                                        : e - this.offset.click.top >= this.containment[1]
                                        ? e - n.grid[1]
                                        : e + n.grid[1]),
                                (i = this.originalPageX + Math.round((s - this.originalPageX) / n.grid[0]) * n.grid[0]),
                                (s =
                                    !this.containment || (i - this.offset.click.left >= this.containment[0] && i - this.offset.click.left <= this.containment[2])
                                        ? i
                                        : i - this.offset.click.left >= this.containment[0]
                                        ? i - n.grid[0]
                                        : i + n.grid[0]))),
                        {
                            top: o - this.offset.click.top - this.offset.relative.top - this.offset.parent.top + ("fixed" === this.cssPosition ? -this.scrollParent.scrollTop() : a ? 0 : r.scrollTop()),
                            left: s - this.offset.click.left - this.offset.relative.left - this.offset.parent.left + ("fixed" === this.cssPosition ? -this.scrollParent.scrollLeft() : a ? 0 : r.scrollLeft()),
                        }
                    );
                },
                _rearrange: function (t, e, i, n) {
                    i ? i[0].appendChild(this.placeholder[0]) : e.item[0].parentNode.insertBefore(this.placeholder[0], "down" === this.direction ? e.item[0] : e.item[0].nextSibling), (this.counter = this.counter ? ++this.counter : 1);
                    var s = this.counter;
                    this._delay(function () {
                        s === this.counter && this.refreshPositions(!n);
                    });
                },
                _clear: function (t, e) {
                    this.reverting = !1;
                    var i,
                        n = [];
                    if ((!this._noFinalSort && this.currentItem.parent().length && this.placeholder.before(this.currentItem), (this._noFinalSort = null), this.helper[0] === this.currentItem[0])) {
                        for (i in this._storedCSS) ("auto" !== this._storedCSS[i] && "static" !== this._storedCSS[i]) || (this._storedCSS[i] = "");
                        this.currentItem.css(this._storedCSS), this._removeClass(this.currentItem, "ui-sortable-helper");
                    } else this.currentItem.show();
                    function s(e, i, n) {
                        return function (t) {
                            n._trigger(e, t, i._uiHash(i));
                        };
                    }
                    for (
                        this.fromOutside &&
                            !e &&
                            n.push(function (t) {
                                this._trigger("receive", t, this._uiHash(this.fromOutside));
                            }),
                            (!this.fromOutside && this.domPosition.prev === this.currentItem.prev().not(".ui-sortable-helper")[0] && this.domPosition.parent === this.currentItem.parent()[0]) ||
                                e ||
                                n.push(function (t) {
                                    this._trigger("update", t, this._uiHash());
                                }),
                            this !== this.currentContainer &&
                                (e ||
                                    (n.push(function (t) {
                                        this._trigger("remove", t, this._uiHash());
                                    }),
                                    n.push(
                                        function (e) {
                                            return function (t) {
                                                e._trigger("receive", t, this._uiHash(this));
                                            };
                                        }.call(this, this.currentContainer)
                                    ),
                                    n.push(
                                        function (e) {
                                            return function (t) {
                                                e._trigger("update", t, this._uiHash(this));
                                            };
                                        }.call(this, this.currentContainer)
                                    ))),
                            i = this.containers.length - 1;
                        0 <= i;
                        i--
                    )
                        e || n.push(s("deactivate", this, this.containers[i])), this.containers[i].containerCache.over && (n.push(s("out", this, this.containers[i])), (this.containers[i].containerCache.over = 0));
                    if (
                        (this.storedCursor && (this.document.find("body").css("cursor", this.storedCursor), this.storedStylesheet.remove()),
                        this._storedOpacity && this.helper.css("opacity", this._storedOpacity),
                        this._storedZIndex && this.helper.css("zIndex", "auto" === this._storedZIndex ? "" : this._storedZIndex),
                        (this.dragging = !1),
                        e || this._trigger("beforeStop", t, this._uiHash()),
                        this.placeholder[0].parentNode.removeChild(this.placeholder[0]),
                        this.cancelHelperRemoval || (this.helper[0] !== this.currentItem[0] && this.helper.remove(), (this.helper = null)),
                        !e)
                    ) {
                        for (i = 0; i < n.length; i++) n[i].call(this, t);
                        this._trigger("stop", t, this._uiHash());
                    }
                    return (this.fromOutside = !1), !this.cancelHelperRemoval;
                },
                _trigger: function () {
                    !1 === x.Widget.prototype._trigger.apply(this, arguments) && this.cancel();
                },
                _uiHash: function (t) {
                    var e = t || this;
                    return { helper: e.helper, placeholder: e.placeholder || x([]), position: e.position, originalPosition: e.originalPosition, offset: e.positionAbs, item: e.currentItem, sender: t ? t.element : null };
                },
            });
    }),
    "undefined" === typeof jQuery)
)
    throw new Error("Bootstrap's JavaScript requires jQuery");
function _typeof(t) {
    return (_typeof =
        "function" === typeof Symbol && "symbol" === typeof Symbol.iterator
            ? function (t) {
                  return typeof t;
              }
            : function (t) {
                  return t && "function" === typeof Symbol && t.constructor === Symbol && t !== Symbol.prototype ? "symbol" : typeof t;
              })(t);
}
function _possibleConstructorReturn(t, e) {
    return !e || ("object" !== _typeof(e) && "function" !==    typeof e) ? _assertThisInitialized(t) : e;
}
function _getPrototypeOf(t) {
    return (_getPrototypeOf = Object.setPrototypeOf
        ? Object.getPrototypeOf
        : function (t) {
              return t.__proto__ || Object.getPrototypeOf(t);
          })(t);
}
function _assertThisInitialized(t) {
    if (void 0 === t) throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
    return t;
}
function _inherits(t, e) {
    if ("function" !==    typeof e && null !== e) throw new TypeError("Super expression must either be null or a function");
    (t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } })), e && _setPrototypeOf(t, e);
}
function _setPrototypeOf(t, e) {
    return (_setPrototypeOf =
        Object.setPrototypeOf ||
        function (t, e) {
            return (t.__proto__ = e), t;
        })(t, e);
}
function _classCallCheck(t, e) {
    if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function");
}
function _defineProperties(t, e) {
    for (var i = 0; i < e.length; i++) {
        var n = e[i];
        (n.enumerable = n.enumerable || !1), (n.configurable = !0), "value" in n && (n.writable = !0), Object.defineProperty(t, n.key, n);
    }
}
function _createClass(t, e, i) {
    return e && _defineProperties(t.prototype, e), i && _defineProperties(t, i), t;
}
!(function () {
    "use strict";
    var t = jQuery.fn.jquery.split(" ")[0].split(".");
    if ((t[0] < 2 && t[1] < 9) || (1 === t[0] && 9 === t[1] && t[2] < 1) || 3 < t[0]) throw new Error("Bootstrap's JavaScript requires jQuery version 1.9.1 or higher, but lower than version 4");
})(),
    (function (n) {
        "use strict";
        (n.fn.emulateTransitionEnd = function (t) {
            var e = !1,
                i = this;
            n(this).one("bsTransitionEnd", function () {
                e = !0;
            });
            return (
                setTimeout(function () {
                    e || n(i).trigger(n.support.transition.end);
                }, t),
                this
            );
        }),
            n(function () {
                (n.support.transition = (function () {
                    var t = document.createElement("bootstrap"),
                        e = { WebkitTransition: "webkitTransitionEnd", MozTransition: "transitionend", OTransition: "oTransitionEnd otransitionend", transition: "transitionend" };
                    for (var i in e) if (void 0 !== t.style[i]) return { end: e[i] };
                    return !1;
                })()),
                    n.support.transition &&
                        (n.event.special.bsTransitionEnd = {
                            bindType: n.support.transition.end,
                            delegateType: n.support.transition.end,
                            handle: function (t) {
                                if (n(t.target).is(this)) return t.handleObj.handler.apply(this, arguments);
                            },
                        });
            });
    })(jQuery),
    (function (o) {
        "use strict";
        function r(t) {
            o(t).on("click", e, this.close);
        }
        var e = '[data-dismiss="alert"]';
        (r.VERSION = "3.4.1"),
            (r.TRANSITION_DURATION = 150),
            (r.prototype.close = function (t) {
                var e = o(this),
                    i = e.attr("data-target");
                i = "#" === (i = i || ((i = e.attr("href")) && i.replace(/.*(?=#[^\s]*$)/, ""))) ? [] : i;
                var n = o(document).find(i);
                function s() {
                    n.detach().trigger("closed.bs.alert").remove();
                }
                t && t.preventDefault(),
                    n.length || (n = e.closest(".alert")),
                    n.trigger((t = o.Event("close.bs.alert"))),
                    t.isDefaultPrevented() || (n.removeClass("in"), o.support.transition && n.hasClass("fade") ? n.one("bsTransitionEnd", s).emulateTransitionEnd(r.TRANSITION_DURATION) : s());
            });
        var t = o.fn.alert;
        (o.fn.alert = function (i) {
            return this.each(function () {
                var t = o(this),
                    e = t.data("bs.alert");
                e || t.data("bs.alert", (e = new r(this))), "string" === typeof i && e[i].call(t);
            });
        }),
            (o.fn.alert.Constructor = r),
            (o.fn.alert.noConflict = function () {
                return (o.fn.alert = t), this;
            }),
            o(document).on("click.bs.alert.data-api", e, r.prototype.close);
    })(jQuery),
    (function (o) {
        "use strict";
        var s = function (t, e) {
            (this.$element = o(t)), (this.options = o.extend({}, s.DEFAULTS, e)), (this.isLoading = !1);
        };
        function i(n) {
            return this.each(function () {
                var t = o(this),
                    e = t.data("bs.button"),
                    i = "object" === typeof n && n;
                e || t.data("bs.button", (e = new s(this, i))), "toggle" === n ? e.toggle() : n && e.setState(n);
            });
        }
        (s.VERSION = "3.4.1"),
            (s.DEFAULTS = { loadingText: "loading..." }),
            (s.prototype.setState = function (t) {
                var e = "disabled",
                    i = this.$element,
                    n = i.is("input") ? "val" : "html",
                    s = i.data();
                (t += "Text"),
                    null === s.resetText && i.data("resetText", i[n]()),
                    setTimeout(
                        o.proxy(function () {
                            i[n](null === s[t] ? this.options[t] : s[t]),
                                "loadingText" === t ? ((this.isLoading = !0), i.addClass(e).attr(e, e).prop(e, !0)) : this.isLoading && ((this.isLoading = !1), i.removeClass(e).removeAttr(e).prop(e, !1));
                        }, this),
                        0
                    );
            }),
            (s.prototype.toggle = function () {
                var t = !0,
                    e = this.$element.closest('[data-toggle="buttons"]');
                if (e.length) {
                    var i = this.$element.find("input");
                    "radio" === i.prop("type")
                        ? (i.prop("checked") && (t = !1), e.find(".active").removeClass("active"), this.$element.addClass("active"))
                        : "checkbox" === i.prop("type") && (i.prop("checked") !== this.$element.hasClass("active") && (t = !1), this.$element.toggleClass("active")),
                        i.prop("checked", this.$element.hasClass("active")),
                        t && i.trigger("change");
                } else this.$element.attr("aria-pressed", !this.$element.hasClass("active")), this.$element.toggleClass("active");
            });
        var t = o.fn.button;
        (o.fn.button = i),
            (o.fn.button.Constructor = s),
            (o.fn.button.noConflict = function () {
                return (o.fn.button = t), this;
            }),
            o(document)
                .on("click.bs.button.data-api", '[data-toggle^="button"]', function (t) {
                    var e = o(t.target).closest(".btn");
                    i.call(e, "toggle"), o(t.target).is('input[type="radio"], input[type="checkbox"]') || (t.preventDefault(), e.is("input,button") ? e.trigger("focus") : e.find("input:visible,button:visible").first().trigger("focus"));
                })
                .on("focus.bs.button.data-api blur.bs.button.data-api", '[data-toggle^="button"]', function (t) {
                    o(t.target)
                        .closest(".btn")
                        .toggleClass("focus", /^focus(in)?$/.test(t.type));
                });
    })(jQuery),
    (function (u) {
        "use strict";
        function d(t, e) {
            (this.$element = u(t)),
                (this.$indicators = this.$element.find(".carousel-indicators")),
                (this.options = e),
                (this.paused = null),
                (this.sliding = null),
                (this.interval = null),
                (this.$active = null),
                (this.$items = null),
                this.options.keyboard && this.$element.on("keydown.bs.carousel", u.proxy(this.keydown, this)),
                "hover" !==    this.options.pause || "ontouchstart" in document.documentElement || this.$element.on("mouseenter.bs.carousel", u.proxy(this.pause, this)).on("mouseleave.bs.carousel", u.proxy(this.cycle, this));
        }
        function a(s) {
            return this.each(function () {
                var t = u(this),
                    e = t.data("bs.carousel"),
                    i = u.extend({}, d.DEFAULTS, t.data(), "object" === typeof s && s),
                    n = "string" === typeof s ? s : i.slide;
                e || t.data("bs.carousel", (e = new d(this, i))), "number" === typeof s ? e.to(s) : n ? e[n]() : i.interval && e.pause().cycle();
            });
        }
        (d.VERSION = "3.4.1"),
            (d.TRANSITION_DURATION = 600),
            (d.DEFAULTS = { interval: 5e3, pause: "hover", wrap: !0, keyboard: !0 }),
            (d.prototype.keydown = function (t) {
                if (!/input|textarea/i.test(t.target.tagName)) {
                    switch (t.which) {
                        case 37:
                            this.prev();
                            break;
                        case 39:
                            this.next();
                            break;
                        default:
                            return;
                    }
                    t.preventDefault();
                }
            }),
            (d.prototype.cycle = function (t) {
                return t || (this.paused = !1), this.interval && clearInterval(this.interval), this.options.interval && !this.paused && (this.interval = setInterval(u.proxy(this.next, this), this.options.interval)), this;
            }),
            (d.prototype.getItemIndex = function (t) {
                return (this.$items = t.parent().children(".item")), this.$items.index(t || this.$active);
            }),
            (d.prototype.getItemForDirection = function (t, e) {
                var i = this.getItemIndex(e);
                if ((("prev" === t && 0 === i) || ("next" === t && i === this.$items.length - 1)) && !this.options.wrap) return e;
                var n = (i + ("prev" === t ? -1 : 1)) % this.$items.length;
                return this.$items.eq(n);
            }),
            (d.prototype.to = function (t) {
                var e = this,
                    i = this.getItemIndex((this.$active = this.$element.find(".item.active")));
                if (!(t > this.$items.length - 1 || t < 0))
                    return this.sliding
                        ? this.$element.one("slid.bs.carousel", function () {
                              e.to(t);
                          })
                        : i === t
                        ? this.pause().cycle()
                        : this.slide(i < t ? "next" : "prev", this.$items.eq(t));
            }),
            (d.prototype.pause = function (t) {
                return t || (this.paused = !0), this.$element.find(".next, .prev").length && u.support.transition && (this.$element.trigger(u.support.transition.end), this.cycle(!0)), (this.interval = clearInterval(this.interval)), this;
            }),
            (d.prototype.next = function () {
                if (!this.sliding) return this.slide("next");
            }),
            (d.prototype.prev = function () {
                if (!this.sliding) return this.slide("prev");
            }),
            (d.prototype.slide = function (t, e) {
                var i = this.$element.find(".item.active"),
                    n = e || this.getItemForDirection(t, i),
                    s = this.interval,
                    o = "next" === t ? "left" : "right",
                    r = this;
                if (n.hasClass("active")) return (this.sliding = !1);
                var a = n[0],
                    l = u.Event("slide.bs.carousel", { relatedTarget: a, direction: o });
                if ((this.$element.trigger(l), !l.isDefaultPrevented())) {
                    if (((this.sliding = !0), s && this.pause(), this.$indicators.length)) {
                        this.$indicators.find(".active").removeClass("active");
                        var c = u(this.$indicators.children()[this.getItemIndex(n)]);
                        c && c.addClass("active");
                    }
                    var h = u.Event("slid.bs.carousel", { relatedTarget: a, direction: o });
                    return (
                        u.support.transition && this.$element.hasClass("slide")
                            ? (n.addClass(t),
                              "object" === typeof n && n.length && n[0].offsetWidth,
                              i.addClass(o),
                              n.addClass(o),
                              i
                                  .one("bsTransitionEnd", function () {
                                      n.removeClass([t, o].join(" ")).addClass("active"),
                                          i.removeClass(["active", o].join(" ")),
                                          (r.sliding = !1),
                                          setTimeout(function () {
                                              r.$element.trigger(h);
                                          }, 0);
                                  })
                                  .emulateTransitionEnd(d.TRANSITION_DURATION))
                            : (i.removeClass("active"), n.addClass("active"), (this.sliding = !1), this.$element.trigger(h)),
                        s && this.cycle(),
                        this
                    );
                }
            });
        var t = u.fn.carousel;
        (u.fn.carousel = a),
            (u.fn.carousel.Constructor = d),
            (u.fn.carousel.noConflict = function () {
                return (u.fn.carousel = t), this;
            });
        function e(t) {
            var e = u(this),
                i = e.attr("href");
            i = i && i.replace(/.*(?=#[^\s]+$)/, "");
            var n = e.attr("data-target") || i,
                s = u(document).find(n);
            if (s.hasClass("carousel")) {
                var o = u.extend({}, s.data(), e.data()),
                    r = e.attr("data-slide-to");
                r && (o.interval = !1), a.call(s, o), r && s.data("bs.carousel").to(r), t.preventDefault();
            }
        }
        u(document).on("click.bs.carousel.data-api", "[data-slide]", e).on("click.bs.carousel.data-api", "[data-slide-to]", e),
            u(window).on("load", function () {
                u('[data-ride="carousel"]').each(function () {
                    var t = u(this);
                    a.call(t, t.data());
                });
            });
    })(jQuery),
    (function (r) {
        "use strict";
        var a = function (t, e) {
            (this.$element = r(t)),
                (this.options = r.extend({}, a.DEFAULTS, e)),
                (this.$trigger = r('[data-toggle="collapse"][href="#' + t.id + '"],[data-toggle="collapse"][data-target="#' + t.id + '"]')),
                (this.transitioning = null),
                this.options.parent ? (this.$parent = this.getParent()) : this.addAriaAndCollapsedClass(this.$element, this.$trigger),
                this.options.toggle && this.toggle();
        };
        function s(t) {
            var e,
                i = t.attr("data-target") || ((e = t.attr("href")) && e.replace(/.*(?=#[^\s]+$)/, ""));
            return r(document).find(i);
        }
        function l(n) {
            return this.each(function () {
                var t = r(this),
                    e = t.data("bs.collapse"),
                    i = r.extend({}, a.DEFAULTS, t.data(), "object" === typeof n && n);
                !e && i.toggle && /show|hide/.test(n) && (i.toggle = !1), e || t.data("bs.collapse", (e = new a(this, i))), "string" === typeof n && e[n]();
            });
        }
        (a.VERSION = "3.4.1"),
            (a.TRANSITION_DURATION = 350),
            (a.DEFAULTS = { toggle: !0 }),
            (a.prototype.dimension = function () {
                return this.$element.hasClass("width") ? "width" : "height";
            }),
            (a.prototype.show = function () {
                if (!this.transitioning && !this.$element.hasClass("in")) {
                    var t,
                        e = this.$parent && this.$parent.children(".panel").children(".in, .collapsing");
                    if (!(e && e.length && (t = e.data("bs.collapse")) && t.transitioning)) {
                        var i = r.Event("show.bs.collapse");
                        if ((this.$element.trigger(i), !i.isDefaultPrevented())) {
                            e && e.length && (l.call(e, "hide"), t || e.data("bs.collapse", null));
                            var n = this.dimension();
                            this.$element.removeClass("collapse").addClass("collapsing")[n](0).attr("aria-expanded", !0), this.$trigger.removeClass("collapsed").attr("aria-expanded", !0), (this.transitioning = 1);
                            var s = function () {
                                this.$element.removeClass("collapsing").addClass("collapse in")[n](""), (this.transitioning = 0), this.$element.trigger("shown.bs.collapse");
                            };
                            if (!r.support.transition) return s.call(this);
                            var o = r.camelCase(["scroll", n].join("-"));
                            this.$element.one("bsTransitionEnd", r.proxy(s, this)).emulateTransitionEnd(a.TRANSITION_DURATION)[n](this.$element[0][o]);
                        }
                    }
                }
            }),
            (a.prototype.hide = function () {
                if (!this.transitioning && this.$element.hasClass("in")) {
                    var t = r.Event("hide.bs.collapse");
                    if ((this.$element.trigger(t), !t.isDefaultPrevented())) {
                        var e = this.dimension();
                        this.$element[e](this.$element[e]())[0].offsetHeight,
                            this.$element.addClass("collapsing").removeClass("collapse in").attr("aria-expanded", !1),
                            this.$trigger.addClass("collapsed").attr("aria-expanded", !1),
                            (this.transitioning = 1);
                        var i = function () {
                            (this.transitioning = 0), this.$element.removeClass("collapsing").addClass("collapse").trigger("hidden.bs.collapse");
                        };
                        if (!r.support.transition) return i.call(this);
                        this.$element[e](0).one("bsTransitionEnd", r.proxy(i, this)).emulateTransitionEnd(a.TRANSITION_DURATION);
                    }
                }
            }),
            (a.prototype.toggle = function () {
                this[this.$element.hasClass("in") ? "hide" : "show"]();
            }),
            (a.prototype.getParent = function () {
                return r(document)
                    .find(this.options.parent)
                    .find('[data-toggle="collapse"][data-parent="' + this.options.parent + '"]')
                    .each(
                        r.proxy(function (t, e) {
                            var i = r(e);
                            this.addAriaAndCollapsedClass(s(i), i);
                        }, this)
                    )
                    .end();
            }),
            (a.prototype.addAriaAndCollapsedClass = function (t, e) {
                var i = t.hasClass("in");
                t.attr("aria-expanded", i), e.toggleClass("collapsed", !i).attr("aria-expanded", i);
            });
        var t = r.fn.collapse;
        (r.fn.collapse = l),
            (r.fn.collapse.Constructor = a),
            (r.fn.collapse.noConflict = function () {
                return (r.fn.collapse = t), this;
            }),
            r(document).on("click.bs.collapse.data-api", '[data-toggle="collapse"]', function (t) {
                var e = r(this);
                e.attr("data-target") || t.preventDefault();
                var i = s(e),
                    n = i.data("bs.collapse") ? "toggle" : e.data();
                l.call(i, n);
            });
    })(jQuery),
    (function (r) {
        "use strict";
        function n(t) {
            r(t).on("click.bs.dropdown", this.toggle);
        }
        var a = '[data-toggle="dropdown"]';
        function l(t) {
            var e = t.attr("data-target"),
                i = "#" !== (e = e || ((e = t.attr("href")) && /#[A-Za-z]/.test(e) && e.replace(/.*(?=#[^\s]*$)/, ""))) ? r(document).find(e) : null;
            return i && i.length ? i : t.parent();
        }
        function o(n) {
            (n && 3 === n.which) ||
                (r(".dropdown-backdrop").remove(),
                r(a).each(function () {
                    var t = r(this),
                        e = l(t),
                        i = { relatedTarget: this };
                    e.hasClass("open") &&
                        ((n && "click" === n.type && /input|textarea/i.test(n.target.tagName) && r.contains(e[0], n.target)) ||
                            (e.trigger((n = r.Event("hide.bs.dropdown", i))), n.isDefaultPrevented() || (t.attr("aria-expanded", "false"), e.removeClass("open").trigger(r.Event("hidden.bs.dropdown", i)))));
                }));
        }
        (n.VERSION = "3.4.1"),
            (n.prototype.toggle = function (t) {
                var e = r(this);
                if (!e.is(".disabled, :disabled")) {
                    var i = l(e),
                        n = i.hasClass("open");
                    if ((o(), !n)) {
                        "ontouchstart" in document.documentElement && !i.closest(".navbar-nav").length && r(document.createElement("div")).addClass("dropdown-backdrop").insertAfter(r(this)).on("click", o);
                        var s = { relatedTarget: this };
                        if ((i.trigger((t = r.Event("show.bs.dropdown", s))), t.isDefaultPrevented())) return;
                        e.trigger("focus").attr("aria-expanded", "true"), i.toggleClass("open").trigger(r.Event("shown.bs.dropdown", s));
                    }
                    return !1;
                }
            }),
            (n.prototype.keydown = function (t) {
                if (/(38|40|27|32)/.test(t.which) && !/input|textarea/i.test(t.target.tagName)) {
                    var e = r(this);
                    if ((t.preventDefault(), t.stopPropagation(), !e.is(".disabled, :disabled"))) {
                        var i = l(e),
                            n = i.hasClass("open");
                        if ((!n && 27 !==    t.which) || (n && 27 === t.which)) return 27 === t.which && i.find(a).trigger("focus"), e.trigger("click");
                        var s = i.find(".dropdown-menu li:not(.disabled):visible a");
                        if (s.length) {
                            var o = s.index(t.target);
                            38 === t.which && 0 < o && o--, 40 === t.which && o < s.length - 1 && o++, ~o || (o = 0), s.eq(o).trigger("focus");
                        }
                    }
                }
            });
        var t = r.fn.dropdown;
        (r.fn.dropdown = function (i) {
            return this.each(function () {
                var t = r(this),
                    e = t.data("bs.dropdown");
                e || t.data("bs.dropdown", (e = new n(this))), "string" === typeof i && e[i].call(t);
            });
        }),
            (r.fn.dropdown.Constructor = n),
            (r.fn.dropdown.noConflict = function () {
                return (r.fn.dropdown = t), this;
            }),
            r(document)
                .on("click.bs.dropdown.data-api", o)
                .on("click.bs.dropdown.data-api", ".dropdown form", function (t) {
                    t.stopPropagation();
                })
                .on("click.bs.dropdown.data-api", a, n.prototype.toggle)
                .on("keydown.bs.dropdown.data-api", a, n.prototype.keydown)
                .on("keydown.bs.dropdown.data-api", ".dropdown-menu", n.prototype.keydown);
    })(jQuery),
    (function (r) {
        "use strict";
        function o(t, e) {
            (this.options = e),
                (this.$body = r(document.body)),
                (this.$element = r(t)),
                (this.$dialog = this.$element.find(".modal-dialog")),
                (this.$backdrop = null),
                (this.isShown = null),
                (this.originalBodyPad = null),
                (this.scrollbarWidth = 0),
                (this.ignoreBackdropClick = !1),
                (this.fixedContent = ".navbar-fixed-top, .navbar-fixed-bottom"),
                this.options.remote &&
                    this.$element.find(".modal-content").load(
                        this.options.remote,
                        r.proxy(function () {
                            this.$element.trigger("loaded.bs.modal");
                        }, this)
                    );
        }
        function a(n, s) {
            return this.each(function () {
                var t = r(this),
                    e = t.data("bs.modal"),
                    i = r.extend({}, o.DEFAULTS, t.data(), "object" === typeof n && n);
                e || t.data("bs.modal", (e = new o(this, i))), "string" === typeof n ? e[n](s) : i.show && e.show(s);
            });
        }
        (o.VERSION = "3.4.1"),
            (o.TRANSITION_DURATION = 300),
            (o.BACKDROP_TRANSITION_DURATION = 150),
            (o.DEFAULTS = { backdrop: !0, keyboard: !0, show: !0 }),
            (o.prototype.toggle = function (t) {
                return this.isShown ? this.hide() : this.show(t);
            }),
            (o.prototype.show = function (i) {
                var n = this,
                    t = r.Event("show.bs.modal", { relatedTarget: i });
                this.$element.trigger(t),
                    this.isShown ||
                        t.isDefaultPrevented() ||
                        ((this.isShown = !0),
                        this.checkScrollbar(),
                        this.setScrollbar(),
                        this.$body.addClass("modal-open"),
                        this.escape(),
                        this.resize(),
                        this.$element.on("click.dismiss.bs.modal", '[data-dismiss="modal"]', r.proxy(this.hide, this)),
                        this.$dialog.on("mousedown.dismiss.bs.modal", function () {
                            n.$element.one("mouseup.dismiss.bs.modal", function (t) {
                                r(t.target).is(n.$element) && (n.ignoreBackdropClick = !0);
                            });
                        }),
                        this.backdrop(function () {
                            var t = r.support.transition && n.$element.hasClass("fade");
                            n.$element.parent().length || n.$element.appendTo(n.$body), n.$element.show().scrollTop(0), n.adjustDialog(), t && n.$element[0].offsetWidth, n.$element.addClass("in"), n.enforceFocus();
                            var e = r.Event("shown.bs.modal", { relatedTarget: i });
                            t
                                ? n.$dialog
                                      .one("bsTransitionEnd", function () {
                                          n.$element.trigger("focus").trigger(e);
                                      })
                                      .emulateTransitionEnd(o.TRANSITION_DURATION)
                                : n.$element.trigger("focus").trigger(e);
                        }));
            }),
            (o.prototype.hide = function (t) {
                t && t.preventDefault(),
                    (t = r.Event("hide.bs.modal")),
                    this.$element.trigger(t),
                    this.isShown &&
                        !t.isDefaultPrevented() &&
                        ((this.isShown = !1),
                        this.escape(),
                        this.resize(),
                        r(document).off("focusin.bs.modal"),
                        this.$element.removeClass("in").off("click.dismiss.bs.modal").off("mouseup.dismiss.bs.modal"),
                        this.$dialog.off("mousedown.dismiss.bs.modal"),
                        r.support.transition && this.$element.hasClass("fade") ? this.$element.one("bsTransitionEnd", r.proxy(this.hideModal, this)).emulateTransitionEnd(o.TRANSITION_DURATION) : this.hideModal());
            }),
            (o.prototype.enforceFocus = function () {
                r(document)
                    .off("focusin.bs.modal")
                    .on(
                        "focusin.bs.modal",
                        r.proxy(function (t) {
                            document === t.target || this.$element[0] === t.target || this.$element.has(t.target).length || this.$element.trigger("focus");
                        }, this)
                    );
            }),
            (o.prototype.escape = function () {
                this.isShown && this.options.keyboard
                    ? this.$element.on(
                          "keydown.dismiss.bs.modal",
                          r.proxy(function (t) {
                              27 === t.which && this.hide();
                          }, this)
                      )
                    : this.isShown || this.$element.off("keydown.dismiss.bs.modal");
            }),
            (o.prototype.resize = function () {
                this.isShown ? r(window).on("resize.bs.modal", r.proxy(this.handleUpdate, this)) : r(window).off("resize.bs.modal");
            }),
            (o.prototype.hideModal = function () {
                var t = this;
                this.$element.hide(),
                    this.backdrop(function () {
                        t.$body.removeClass("modal-open"), t.resetAdjustments(), t.resetScrollbar(), t.$element.trigger("hidden.bs.modal");
                    });
            }),
            (o.prototype.removeBackdrop = function () {
                this.$backdrop && this.$backdrop.remove(), (this.$backdrop = null);
            }),
            (o.prototype.backdrop = function (t) {
                var e = this,
                    i = this.$element.hasClass("fade") ? "fade" : "";
                if (this.isShown && this.options.backdrop) {
                    var n = r.support.transition && i;
                    if (
                        ((this.$backdrop = r(document.createElement("div"))
                            .addClass("modal-backdrop " + i)
                            .appendTo(this.$body)),
                        this.$element.on(
                            "click.dismiss.bs.modal",
                            r.proxy(function (t) {
                                this.ignoreBackdropClick ? (this.ignoreBackdropClick = !1) : t.target === t.currentTarget && ("static" === this.options.backdrop ? this.$element[0].focus() : this.hide());
                            }, this)
                        ),
                        n && this.$backdrop[0].offsetWidth,
                        this.$backdrop.addClass("in"),
                        !t)
                    )
                        return;
                    n ? this.$backdrop.one("bsTransitionEnd", t).emulateTransitionEnd(o.BACKDROP_TRANSITION_DURATION) : t();
                } else if (!this.isShown && this.$backdrop) {
                    this.$backdrop.removeClass("in");
                    var s = function () {
                        e.removeBackdrop(), t && t();
                    };
                    r.support.transition && this.$element.hasClass("fade") ? this.$backdrop.one("bsTransitionEnd", s).emulateTransitionEnd(o.BACKDROP_TRANSITION_DURATION) : s();
                } else t && t();
            }),
            (o.prototype.handleUpdate = function () {
                this.adjustDialog();
            }),
            (o.prototype.adjustDialog = function () {
                var t = this.$element[0].scrollHeight > document.documentElement.clientHeight;
                this.$element.css({ paddingLeft: !this.bodyIsOverflowing && t ? this.scrollbarWidth : "", paddingRight: this.bodyIsOverflowing && !t ? this.scrollbarWidth : "" });
            }),
            (o.prototype.resetAdjustments = function () {
                this.$element.css({ paddingLeft: "", paddingRight: "" });
            }),
            (o.prototype.checkScrollbar = function () {
                var t = window.innerWidth;
                if (!t) {
                    var e = document.documentElement.getBoundingClientRect();
                    t = e.right - Math.abs(e.left);
                }
                (this.bodyIsOverflowing = document.body.clientWidth < t), (this.scrollbarWidth = this.measureScrollbar());
            }),
            (o.prototype.setScrollbar = function () {
                var t = parseInt(this.$body.css("padding-right") || 0, 10);
                this.originalBodyPad = document.body.style.paddingRight || "";
                var s = this.scrollbarWidth;
                this.bodyIsOverflowing &&
                    (this.$body.css("padding-right", t + s),
                    r(this.fixedContent).each(function (t, e) {
                        var i = e.style.paddingRight,
                            n = r(e).css("padding-right");
                        r(e)
                            .data("padding-right", i)
                            .css("padding-right", parseFloat(n) + s + "px");
                    }));
            }),
            (o.prototype.resetScrollbar = function () {
                this.$body.css("padding-right", this.originalBodyPad),
                    r(this.fixedContent).each(function (t, e) {
                        var i = r(e).data("padding-right");
                        r(e).removeData("padding-right"), (e.style.paddingRight = i || "");
                    });
            }),
            (o.prototype.measureScrollbar = function () {
                var t = document.createElement("div");
                (t.className = "modal-scrollbar-measure"), this.$body.append(t);
                var e = t.offsetWidth - t.clientWidth;
                return this.$body[0].removeChild(t), e;
            });
        var t = r.fn.modal;
        (r.fn.modal = a),
            (r.fn.modal.Constructor = o),
            (r.fn.modal.noConflict = function () {
                return (r.fn.modal = t), this;
            }),
            r(document).on("click.bs.modal.data-api", '[data-toggle="modal"]', function (t) {
                var e = r(this),
                    i = e.attr("href"),
                    n = e.attr("data-target") || (i && i.replace(/.*(?=#[^\s]+$)/, "")),
                    s = r(document).find(n),
                    o = s.data("bs.modal") ? "toggle" : r.extend({ remote: !/#/.test(i) && i }, s.data(), e.data());
                e.is("a") && t.preventDefault(),
                    s.one("show.bs.modal", function (t) {
                        t.isDefaultPrevented() ||
                            s.one("hidden.bs.modal", function () {
                                e.is(":visible") && e.trigger("focus");
                            });
                    }),
                    a.call(s, o, this);
            });
    })(jQuery),
    (function (g) {
        "use strict";
        var n = ["sanitize", "whiteList", "sanitizeFn"],
            r = ["background", "cite", "href", "itemtype", "longdesc", "poster", "src", "xlink:href"],
            t = {
                "*": ["class", "dir", "id", "lang", "role", /^aria-[\w-]*$/i],
                a: ["target", "href", "title", "rel"],
                area: [],
                b: [],
                br: [],
                col: [],
                code: [],
                div: [],
                em: [],
                hr: [],
                h1: [],
                h2: [],
                h3: [],
                h4: [],
                h5: [],
                h6: [],
                i: [],
                img: ["src", "alt", "title", "width", "height"],
                li: [],
                ol: [],
                p: [],
                pre: [],
                s: [],
                small: [],
                span: [],
                sub: [],
                sup: [],
                strong: [],
                u: [],
                ul: [],
            },
            a = /^(?:(?:https?|mailto|ftp|tel|file):|[^&:/?#]*(?:[/?#]|$))/gi,
            l = /^data:(?:image\/(?:bmp|gif|jpeg|jpg|png|tiff|webp)|video\/(?:mpeg|mp4|ogg|webm)|audio\/(?:mp3|oga|ogg|opus));base64,[a-z0-9+/]+=*$/i;
        function f(t, e) {
            var i = t.nodeName.toLowerCase();
            if (-1 !== g.inArray(i, e)) return -1 === g.inArray(i, r) || Boolean(t.nodeValue.match(a) || t.nodeValue.match(l));
            for (
                var n = g(e).filter(function (t, e) {
                        return e instanceof RegExp;
                    }),
                    s = 0,
                    o = n.length;
                s < o;
                s++
            )
                if (i.match(n[s])) return 1;
        }
        function s(t, e, i) {
            if (0 === t.length) return t;
            if (i && "function" === typeof i) return i(t);
            if (!document.implementation || !document.implementation.createHTMLDocument) return t;
            var n = document.implementation.createHTMLDocument("sanitization");
            n.body.innerHTML = t;
            for (
                var s = g.map(e, function (t, e) {
                        return e;
                    }),
                    o = g(n.body).find("*"),
                    r = 0,
                    a = o.length;
                r < a;
                r++
            ) {
                var l = o[r],
                    c = l.nodeName.toLowerCase();
                if (-1 !== g.inArray(c, s))
                    for (
                        var h = g.map(l.attributes, function (t) {
                                return t;
                            }),
                            u = [].concat(e["*"] || [], e[c] || []),
                            d = 0,
                            p = h.length;
                        d < p;
                        d++
                    )
                        f(h[d], u) || l.removeAttribute(h[d].nodeName);
                else l.parentNode.removeChild(l);
            }
            return n.body.innerHTML;
        }
        function m(t, e) {
            (this.type = null), (this.options = null), (this.enabled = null), (this.timeout = null), (this.hoverState = null), (this.$element = null), (this.inState = null), this.init("tooltip", t, e);
        }
        (m.VERSION = "3.4.1"),
            (m.TRANSITION_DURATION = 150),
            (m.DEFAULTS = {
                animation: !0,
                placement: "top",
                selector: !1,
                template: '<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>',
                trigger: "hover focus",
                title: "",
                delay: 0,
                html: !1,
                container: !1,
                viewport: { selector: "body", padding: 0 },
                sanitize: !0,
                sanitizeFn: null,
                whiteList: t,
            }),
            (m.prototype.init = function (t, e, i) {
                if (
                    ((this.enabled = !0),
                    (this.type = t),
                    (this.$element = g(e)),
                    (this.options = this.getOptions(i)),
                    (this.$viewport = this.options.viewport && g(document).find(g.isFunction(this.options.viewport) ? this.options.viewport.call(this, this.$element) : this.options.viewport.selector || this.options.viewport)),
                    (this.inState = { click: !1, hover: !1, focus: !1 }),
                    this.$element[0] instanceof document.constructor && !this.options.selector)
                )
                    throw new Error("`selector` option must be specified when initializing " + this.type + " on the window.document object!");
                for (var n = this.options.trigger.split(" "), s = n.length; s--; ) {
                    var o = n[s];
                    if ("click" === o) this.$element.on("click." + this.type, this.options.selector, g.proxy(this.toggle, this));
                    else if ("manual" !==    o) {
                        var r = "hover" === o ? "mouseenter" : "focusin",
                            a = "hover" === o ? "mouseleave" : "focusout";
                        this.$element.on(r + "." + this.type, this.options.selector, g.proxy(this.enter, this)), this.$element.on(a + "." + this.type, this.options.selector, g.proxy(this.leave, this));
                    }
                }
                this.options.selector ? (this._options = g.extend({}, this.options, { trigger: "manual", selector: "" })) : this.fixTitle();
            }),
            (m.prototype.getDefaults = function () {
                return m.DEFAULTS;
            }),
            (m.prototype.getOptions = function (t) {
                var e = this.$element.data();
                for (var i in e) e.hasOwnProperty(i) && -1 !== g.inArray(i, n) && delete e[i];
                return (t = g.extend({}, this.getDefaults(), e, t)).delay && "number" === typeof t.delay && (t.delay = { show: t.delay, hide: t.delay }), t.sanitize && (t.template = s(t.template, t.whiteList, t.sanitizeFn)), t;
            }),
            (m.prototype.getDelegateOptions = function () {
                var i = {},
                    n = this.getDefaults();
                return (
                    this._options &&
                        g.each(this._options, function (t, e) {
                            n[t] !==    e && (i[t] = e);
                        }),
                    i
                );
            }),
            (m.prototype.enter = function (t) {
                var e = t instanceof this.constructor ? t : g(t.currentTarget).data("bs." + this.type);
                if (
                    (e || ((e = new this.constructor(t.currentTarget, this.getDelegateOptions())), g(t.currentTarget).data("bs." + this.type, e)),
                    t instanceof g.Event && (e.inState["focusin" === t.type ? "focus" : "hover"] = !0),
                    e.tip().hasClass("in") || "in" === e.hoverState)
                )
                    e.hoverState = "in";
                else {
                    if ((clearTimeout(e.timeout), (e.hoverState = "in"), !e.options.delay || !e.options.delay.show)) return e.show();
                    e.timeout = setTimeout(function () {
                        "in" === e.hoverState && e.show();
                    }, e.options.delay.show);
                }
            }),
            (m.prototype.isInStateTrue = function () {
                for (var t in this.inState) if (this.inState[t]) return !0;
                return !1;
            }),
            (m.prototype.leave = function (t) {
                var e = t instanceof this.constructor ? t : g(t.currentTarget).data("bs." + this.type);
                if (
                    (e || ((e = new this.constructor(t.currentTarget, this.getDelegateOptions())), g(t.currentTarget).data("bs." + this.type, e)),
                    t instanceof g.Event && (e.inState["focusout" === t.type ? "focus" : "hover"] = !1),
                    !e.isInStateTrue())
                ) {
                    if ((clearTimeout(e.timeout), (e.hoverState = "out"), !e.options.delay || !e.options.delay.hide)) return e.hide();
                    e.timeout = setTimeout(function () {
                        "out" === e.hoverState && e.hide();
                    }, e.options.delay.hide);
                }
            }),
            (m.prototype.show = function () {
                var t = g.Event("show.bs." + this.type);
                if (this.hasContent() && this.enabled) {
                    this.$element.trigger(t);
                    var e = g.contains(this.$element[0].ownerDocument.documentElement, this.$element[0]);
                    if (t.isDefaultPrevented() || !e) return;
                    var i = this,
                        n = this.tip(),
                        s = this.getUID(this.type);
                    this.setContent(), n.attr("id", s), this.$element.attr("aria-describedby", s), this.options.animation && n.addClass("fade");
                    var o = "function" === typeof this.options.placement ? this.options.placement.call(this, n[0], this.$element[0]) : this.options.placement,
                        r = /\s?auto?\s?/i,
                        a = r.test(o);
                    a && (o = o.replace(r, "") || "top"),
                        n
                            .detach()
                            .css({ top: 0, left: 0, display: "block" })
                            .addClass(o)
                            .data("bs." + this.type, this),
                        this.options.container ? n.appendTo(g(document).find(this.options.container)) : n.insertAfter(this.$element),
                        this.$element.trigger("inserted.bs." + this.type);
                    var l = this.getPosition(),
                        c = n[0].offsetWidth,
                        h = n[0].offsetHeight;
                    if (a) {
                        var u = o,
                            d = this.getPosition(this.$viewport);
                        (o = "bottom" === o && l.bottom + h > d.bottom ? "top" : "top" === o && l.top - h < d.top ? "bottom" : "right" === o && l.right + c > d.width ? "left" : "left" === o && l.left - c < d.left ? "right" : o),
                            n.removeClass(u).addClass(o);
                    }
                    var p = this.getCalculatedOffset(o, l, c, h);
                    this.applyPlacement(p, o);
                    var f = function () {
                        var t = i.hoverState;
                        i.$element.trigger("shown.bs." + i.type), (i.hoverState = null), "out" === t && i.leave(i);
                    };
                    g.support.transition && this.$tip.hasClass("fade") ? n.one("bsTransitionEnd", f).emulateTransitionEnd(m.TRANSITION_DURATION) : f();
                }
            }),
            (m.prototype.applyPlacement = function (t, e) {
                var i = this.tip(),
                    n = i[0].offsetWidth,
                    s = i[0].offsetHeight,
                    o = parseInt(i.css("margin-top"), 10),
                    r = parseInt(i.css("margin-left"), 10);
                isNaN(o) && (o = 0),
                    isNaN(r) && (r = 0),
                    (t.top += o),
                    (t.left += r),
                    g.offset.setOffset(
                        i[0],
                        g.extend(
                            {
                                using: function (t) {
                                    i.css({ top: Math.round(t.top), left: Math.round(t.left) });
                                },
                            },
                            t
                        ),
                        0
                    ),
                    i.addClass("in");
                var a = i[0].offsetWidth,
                    l = i[0].offsetHeight;
                "top" === e && l !==    s && (t.top = t.top + s - l);
                var c = this.getViewportAdjustedDelta(e, t, a, l);
                c.left ? (t.left += c.left) : (t.top += c.top);
                var h = /top|bottom/.test(e),
                    u = h ? 2 * c.left - n + a : 2 * c.top - s + l,
                    d = h ? "offsetWidth" : "offsetHeight";
                i.offset(t), this.replaceArrow(u, i[0][d], h);
            }),
            (m.prototype.replaceArrow = function (t, e, i) {
                this.arrow()
                    .css(i ? "left" : "top", 50 * (1 - t / e) + "%")
                    .css(i ? "top" : "left", "");
            }),
            (m.prototype.setContent = function () {
                var t = this.tip(),
                    e = this.getTitle();
                this.options.html ? (this.options.sanitize && (e = s(e, this.options.whiteList, this.options.sanitizeFn)), t.find(".tooltip-inner").html(e)) : t.find(".tooltip-inner").text(e), t.removeClass("fade in top bottom left right");
            }),
            (m.prototype.hide = function (t) {
                var e = this,
                    i = g(this.$tip),
                    n = g.Event("hide.bs." + this.type);
                function s() {
                    "in" !==    e.hoverState && i.detach(), e.$element && e.$element.removeAttr("aria-describedby").trigger("hidden.bs." + e.type), t && t();
                }
                if ((this.$element.trigger(n), !n.isDefaultPrevented()))
                    return i.removeClass("in"), g.support.transition && i.hasClass("fade") ? i.one("bsTransitionEnd", s).emulateTransitionEnd(m.TRANSITION_DURATION) : s(), (this.hoverState = null), this;
            }),
            (m.prototype.fixTitle = function () {
                var t = this.$element;
                (!t.attr("title") && "string" === typeof t.attr("data-original-title")) || t.attr("data-original-title", t.attr("title") || "").attr("title", "");
            }),
            (m.prototype.hasContent = function () {
                return this.getTitle();
            }),
            (m.prototype.getPosition = function (t) {
                var e = (t = t || this.$element)[0],
                    i = "BODY" === e.tagName,
                    n = e.getBoundingClientRect();
                null === n.width && (n = g.extend({}, n, { width: n.right - n.left, height: n.bottom - n.top }));
                var s = window.SVGElement && e instanceof window.SVGElement,
                    o = i ? { top: 0, left: 0 } : s ? null : t.offset(),
                    r = { scroll: i ? document.documentElement.scrollTop || document.body.scrollTop : t.scrollTop() },
                    a = i ? { width: g(window).width(), height: g(window).height() } : null;
                return g.extend({}, n, r, a, o);
            }),
            (m.prototype.getCalculatedOffset = function (t, e, i, n) {
                return "bottom" === t
                    ? { top: e.top + e.height, left: e.left + e.width / 2 - i / 2 }
                    : "top" === t
                    ? { top: e.top - n, left: e.left + e.width / 2 - i / 2 }
                    : "left" === t
                    ? { top: e.top + e.height / 2 - n / 2, left: e.left - i }
                    : { top: e.top + e.height / 2 - n / 2, left: e.left + e.width };
            }),
            (m.prototype.getViewportAdjustedDelta = function (t, e, i, n) {
                var s = { top: 0, left: 0 };
                if (!this.$viewport) return s;
                var o = (this.options.viewport && this.options.viewport.padding) || 0,
                    r = this.getPosition(this.$viewport);
                if (/right|left/.test(t)) {
                    var a = e.top - o - r.scroll,
                        l = e.top + o - r.scroll + n;
                    a < r.top ? (s.top = r.top - a) : l > r.top + r.height && (s.top = r.top + r.height - l);
                } else {
                    var c = e.left - o,
                        h = e.left + o + i;
                    c < r.left ? (s.left = r.left - c) : h > r.right && (s.left = r.left + r.width - h);
                }
                return s;
            }),
            (m.prototype.getTitle = function () {
                var t = this.$element,
                    e = this.options;
                return t.attr("data-original-title") || ("function" === typeof e.title ? e.title.call(t[0]) : e.title);
            }),
            (m.prototype.getUID = function (t) {
                for (; (t += ~~(1e6 * Math.random())), document.getElementById(t); );
                return t;
            }),
            (m.prototype.tip = function () {
                if (!this.$tip && ((this.$tip = g(this.options.template)), 1 !==    this.$tip.length)) throw new Error(this.type + " `template` option must consist of exactly 1 top-level element!");
                return this.$tip;
            }),
            (m.prototype.arrow = function () {
                return (this.$arrow = this.$arrow || this.tip().find(".tooltip-arrow"));
            }),
            (m.prototype.enable = function () {
                this.enabled = !0;
            }),
            (m.prototype.disable = function () {
                this.enabled = !1;
            }),
            (m.prototype.toggleEnabled = function () {
                this.enabled = !this.enabled;
            }),
            (m.prototype.toggle = function (t) {
                var e = this;
                t && ((e = g(t.currentTarget).data("bs." + this.type)) || ((e = new this.constructor(t.currentTarget, this.getDelegateOptions())), g(t.currentTarget).data("bs." + this.type, e))),
                    t ? ((e.inState.click = !e.inState.click), e.isInStateTrue() ? e.enter(e) : e.leave(e)) : e.tip().hasClass("in") ? e.leave(e) : e.enter(e);
            }),
            (m.prototype.destroy = function () {
                var t = this;
                clearTimeout(this.timeout),
                    this.hide(function () {
                        t.$element.off("." + t.type).removeData("bs." + t.type), t.$tip && t.$tip.detach(), (t.$tip = null), (t.$arrow = null), (t.$viewport = null), (t.$element = null);
                    });
            }),
            (m.prototype.sanitizeHtml = function (t) {
                return s(t, this.options.whiteList, this.options.sanitizeFn);
            });
        var e = g.fn.tooltip;
        (g.fn.tooltip = function (n) {
            return this.each(function () {
                var t = g(this),
                    e = t.data("bs.tooltip"),
                    i = "object" === typeof n && n;
                (!e && /destroy|hide/.test(n)) || (e || t.data("bs.tooltip", (e = new m(this, i))), "string" === typeof n && e[n]());
            });
        }),
            (g.fn.tooltip.Constructor = m),
            (g.fn.tooltip.noConflict = function () {
                return (g.fn.tooltip = e), this;
            });
    })(jQuery),
    (function (s) {
        "use strict";
        function o(t, e) {
            this.init("popover", t, e);
        }
        if (!s.fn.tooltip) throw new Error("Popover requires tooltip.js");
        (o.VERSION = "3.4.1"),
            (o.DEFAULTS = s.extend({}, s.fn.tooltip.Constructor.DEFAULTS, {
                placement: "right",
                trigger: "click",
                content: "",
                template: '<div class="popover" role="tooltip"><div class="arrow"></div><h3 class="popover-title"></h3><div class="popover-content"></div></div>',
            })),
            (((o.prototype = s.extend({}, s.fn.tooltip.Constructor.prototype)).constructor = o).prototype.getDefaults = function () {
                return o.DEFAULTS;
            }),
            (o.prototype.setContent = function () {
                var t = this.tip(),
                    e = this.getTitle(),
                    i = this.getContent();
                if (this.options.html) {
                    var n = typeof i;
                    this.options.sanitize && ((e = this.sanitizeHtml(e)), "string" === n && (i = this.sanitizeHtml(i))),
                        t.find(".popover-title").html(e),
                        t.find(".popover-content").children().detach().end()["string" === n ? "html" : "append"](i);
                } else t.find(".popover-title").text(e), t.find(".popover-content").children().detach().end().text(i);
                t.removeClass("fade top bottom left right in"), t.find(".popover-title").html() || t.find(".popover-title").hide();
            }),
            (o.prototype.hasContent = function () {
                return this.getTitle() || this.getContent();
            }),
            (o.prototype.getContent = function () {
                var t = this.$element,
                    e = this.options;
                return t.attr("data-content") || ("function" === typeof e.content ? e.content.call(t[0]) : e.content);
            }),
            (o.prototype.arrow = function () {
                return (this.$arrow = this.$arrow || this.tip().find(".arrow"));
            });
        var t = s.fn.popover;
        (s.fn.popover = function (n) {
            return this.each(function () {
                var t = s(this),
                    e = t.data("bs.popover"),
                    i = "object" === typeof n && n;
                (!e && /destroy|hide/.test(n)) || (e || t.data("bs.popover", (e = new o(this, i))), "string" === typeof n && e[n]());
            });
        }),
            (s.fn.popover.Constructor = o),
            (s.fn.popover.noConflict = function () {
                return (s.fn.popover = t), this;
            });
    })(jQuery),
    (function (o) {
        "use strict";
        function s(t, e) {
            (this.$body = o(document.body)),
                (this.$scrollElement = o(t).is(document.body) ? o(window) : o(t)),
                (this.options = o.extend({}, s.DEFAULTS, e)),
                (this.selector = (this.options.target || "") + " .nav li > a"),
                (this.offsets = []),
                (this.targets = []),
                (this.activeTarget = null),
                (this.scrollHeight = 0),
                this.$scrollElement.on("scroll.bs.scrollspy", o.proxy(this.process, this)),
                this.refresh(),
                this.process();
        }
        function e(n) {
            return this.each(function () {
                var t = o(this),
                    e = t.data("bs.scrollspy"),
                    i = "object" === typeof n && n;
                e || t.data("bs.scrollspy", (e = new s(this, i))), "string" === typeof n && e[n]();
            });
        }
        (s.VERSION = "3.4.1"),
            (s.DEFAULTS = { offset: 10 }),
            (s.prototype.getScrollHeight = function () {
                return this.$scrollElement[0].scrollHeight || Math.max(this.$body[0].scrollHeight, document.documentElement.scrollHeight);
            }),
            (s.prototype.refresh = function () {
                var t = this,
                    n = "offset",
                    s = 0;
                (this.offsets = []),
                    (this.targets = []),
                    (this.scrollHeight = this.getScrollHeight()),
                    o.isWindow(this.$scrollElement[0]) || ((n = "position"), (s = this.$scrollElement.scrollTop())),
                    this.$body
                        .find(this.selector)
                        .map(function () {
                            var t = o(this),
                                e = t.data("target") || t.attr("href"),
                                i = /^#./.test(e) && o(e);
                            return i && i.length && i.is(":visible") ? [[i[n]().top + s, e]] : null;
                        })
                        .sort(function (t, e) {
                            return t[0] - e[0];
                        })
                        .each(function () {
                            t.offsets.push(this[0]), t.targets.push(this[1]);
                        });
            }),
            (s.prototype.process = function () {
                var t,
                    e = this.$scrollElement.scrollTop() + this.options.offset,
                    i = this.getScrollHeight(),
                    n = this.options.offset + i - this.$scrollElement.height(),
                    s = this.offsets,
                    o = this.targets,
                    r = this.activeTarget;
                if ((this.scrollHeight !==    i && this.refresh(), n <= e)) return r !==    (t = o[o.length - 1]) && this.activate(t);
                if (r && e < s[0]) return (this.activeTarget = null), this.clear();
                for (t = s.length; t--; ) r !==    o[t] && e >= s[t] && (void 0 === s[t + 1] || e < s[t + 1]) && this.activate(o[t]);
            }),
            (s.prototype.activate = function (t) {
                (this.activeTarget = t), this.clear();
                var e = this.selector + '[data-target="' + t + '"],' + this.selector + '[href="' + t + '"]',
                    i = o(e).parents("li").addClass("active");
                i.parent(".dropdown-menu").length && (i = i.closest("li.dropdown").addClass("active")), i.trigger("activate.bs.scrollspy");
            }),
            (s.prototype.clear = function () {
                o(this.selector).parentsUntil(this.options.target, ".active").removeClass("active");
            });
        var t = o.fn.scrollspy;
        (o.fn.scrollspy = e),
            (o.fn.scrollspy.Constructor = s),
            (o.fn.scrollspy.noConflict = function () {
                return (o.fn.scrollspy = t), this;
            }),
            o(window).on("load.bs.scrollspy.data-api", function () {
                o('[data-spy="scroll"]').each(function () {
                    var t = o(this);
                    e.call(t, t.data());
                });
            });
    })(jQuery),
    (function (a) {
        "use strict";
        function r(t) {
            this.element = a(t);
        }
        function e(i) {
            return this.each(function () {
                var t = a(this),
                    e = t.data("bs.tab");
                e || t.data("bs.tab", (e = new r(this))), "string" === typeof i && e[i]();
            });
        }
        (r.VERSION = "3.4.1"),
            (r.TRANSITION_DURATION = 150),
            (r.prototype.show = function () {
                var t = this.element,
                    e = t.closest("ul:not(.dropdown-menu)"),
                    i = t.data("target");
                if (((i = i || ((i = t.attr("href")) && i.replace(/.*(?=#[^\s]*$)/, ""))), !t.parent("li").hasClass("active"))) {
                    var n = e.find(".active:last a"),
                        s = a.Event("hide.bs.tab", { relatedTarget: t[0] }),
                        o = a.Event("show.bs.tab", { relatedTarget: n[0] });
                    if ((n.trigger(s), t.trigger(o), !o.isDefaultPrevented() && !s.isDefaultPrevented())) {
                        var r = a(document).find(i);
                        this.activate(t.closest("li"), e),
                            this.activate(r, r.parent(), function () {
                                n.trigger({ type: "hidden.bs.tab", relatedTarget: t[0] }), t.trigger({ type: "shown.bs.tab", relatedTarget: n[0] });
                            });
                    }
                }
            }),
            (r.prototype.activate = function (t, e, i) {
                var n = e.find("> .active"),
                    s = i && a.support.transition && ((n.length && n.hasClass("fade")) || !!e.find("> .fade").length);
                function o() {
                    n.removeClass("active").find("> .dropdown-menu > .active").removeClass("active").end().find('[data-toggle="tab"]').attr("aria-expanded", !1),
                        t.addClass("active").find('[data-toggle="tab"]').attr("aria-expanded", !0),
                        s ? (t[0].offsetWidth, t.addClass("in")) : t.removeClass("fade"),
                        t.parent(".dropdown-menu").length && t.closest("li.dropdown").addClass("active").end().find('[data-toggle="tab"]').attr("aria-expanded", !0),
                        i && i();
                }
                n.length && s ? n.one("bsTransitionEnd", o).emulateTransitionEnd(r.TRANSITION_DURATION) : o(), n.removeClass("in");
            });
        var t = a.fn.tab;
        (a.fn.tab = e),
            (a.fn.tab.Constructor = r),
            (a.fn.tab.noConflict = function () {
                return (a.fn.tab = t), this;
            });
        function i(t) {
            t.preventDefault(), e.call(a(this), "show");
        }
        a(document).on("click.bs.tab.data-api", '[data-toggle="tab"]', i).on("click.bs.tab.data-api", '[data-toggle="pill"]', i);
    })(jQuery),
    (function (l) {
        "use strict";
        var c = function (t, e) {
            this.options = l.extend({}, c.DEFAULTS, e);
            var i = this.options.target === c.DEFAULTS.target ? l(this.options.target) : l(document).find(this.options.target);
            (this.$target = i.on("scroll.bs.affix.data-api", l.proxy(this.checkPosition, this)).on("click.bs.affix.data-api", l.proxy(this.checkPositionWithEventLoop, this))),
                (this.$element = l(t)),
                (this.affixed = null),
                (this.unpin = null),
                (this.pinnedOffset = null),
                this.checkPosition();
        };
        function i(n) {
            return this.each(function () {
                var t = l(this),
                    e = t.data("bs.affix"),
                    i = "object" === typeof n && n;
                e || t.data("bs.affix", (e = new c(this, i))), "string" === typeof n && e[n]();
            });
        }
        (c.VERSION = "3.4.1"),
            (c.RESET = "affix affix-top affix-bottom"),
            (c.DEFAULTS = { offset: 0, target: window }),
            (c.prototype.getState = function (t, e, i, n) {
                var s = this.$target.scrollTop(),
                    o = this.$element.offset(),
                    r = this.$target.height();
                if (null !==    i && "top" === this.affixed) return s < i && "top";
                if ("bottom" === this.affixed) return null !==    i ? !(s + this.unpin <= o.top) && "bottom" : !(s + r <= t - n) && "bottom";
                var a = null === this.affixed,
                    l = a ? s : o.top;
                return null !==    i && s <= i ? "top" : null !==    n && t - n <= l + (a ? r : e) && "bottom";
            }),
            (c.prototype.getPinnedOffset = function () {
                if (this.pinnedOffset) return this.pinnedOffset;
                this.$element.removeClass(c.RESET).addClass("affix");
                var t = this.$target.scrollTop(),
                    e = this.$element.offset();
                return (this.pinnedOffset = e.top - t);
            }),
            (c.prototype.checkPositionWithEventLoop = function () {
                setTimeout(l.proxy(this.checkPosition, this), 1);
            }),
            (c.prototype.checkPosition = function () {
                if (this.$element.is(":visible")) {
                    var t = this.$element.height(),
                        e = this.options.offset,
                        i = e.top,
                        n = e.bottom,
                        s = Math.max(l(document).height(), l(document.body).height());
                    "object" !==    typeof e && (n = i = e), "function" === typeof i && (i = e.top(this.$element)), "function" === typeof n && (n = e.bottom(this.$element));
                    var o = this.getState(s, t, i, n);
                    if (this.affixed !==    o) {
                        null !==    this.unpin && this.$element.css("top", "");
                        var r = "affix" + (o ? "-" + o : ""),
                            a = l.Event(r + ".bs.affix");
                        if ((this.$element.trigger(a), a.isDefaultPrevented())) return;
                        (this.affixed = o),
                            (this.unpin = "bottom" === o ? this.getPinnedOffset() : null),
                            this.$element
                                .removeClass(c.RESET)
                                .addClass(r)
                                .trigger(r.replace("affix", "affixed") + ".bs.affix");
                    }
                    "bottom" === o && this.$element.offset({ top: s - t - n });
                }
            });
        var t = l.fn.affix;
        (l.fn.affix = i),
            (l.fn.affix.Constructor = c),
            (l.fn.affix.noConflict = function () {
                return (l.fn.affix = t), this;
            }),
            l(window).on("load", function () {
                l('[data-spy="affix"]').each(function () {
                    var t = l(this),
                        e = t.data();
                    (e.offset = e.offset || {}), null !==    e.offsetBottom && (e.offset.bottom = e.offsetBottom), null !==    e.offsetTop && (e.offset.top = e.offsetTop), i.call(t, e);
                });
            });
    })(jQuery),
    (function (t) {
        "function" === typeof define && define.amd ? define(["jquery"], t) : "object" === typeof exports ? t(require("jquery")) : t(jQuery);
    })(function (E, A) {
        function P() {
            return new Date(Date.UTC.apply(Date, arguments));
        }
        function $() {
            var t = new Date();
            return P(t.getFullYear(), t.getMonth(), t.getDate());
        }
        function o(t, e) {
            return t.getUTCFullYear() === e.getUTCFullYear() && t.getUTCMonth() === e.getUTCMonth() && t.getUTCDate() === e.getUTCDate();
        }
        function t(t, e) {
            return function () {
                return e !== A && E.fn.datepicker.deprecated(e), this[t].apply(this, arguments);
            };
        }
        function _(t, e) {
            E.data(t, "datepicker", this),
                (this._events = []),
                (this._secondaryEvents = []),
                this._process_options(e),
                (this.dates = new i()),
                (this.viewDate = this.o.defaultViewDate),
                (this.focusDate = null),
                (this.element = E(t)),
                (this.isInput = this.element.is("input")),
                (this.inputField = this.isInput ? this.element : this.element.find("input")),
                (this.component = !!this.element.hasClass("date") && this.element.find(".add-on, .input-group-addon, .input-group-append, .input-group-prepend, .btn")),
                this.component && 0 === this.component.length && (this.component = !1),
                (this.isInline = !this.component && this.element.is("div")),
                (this.picker = E(z.template)),
                this._check_template(this.o.templates.leftArrow) && this.picker.find(".prev").html(this.o.templates.leftArrow),
                this._check_template(this.o.templates.rightArrow) && this.picker.find(".next").html(this.o.templates.rightArrow),
                this._buildEvents(),
                this._attachEvents(),
                this.isInline ? this.picker.addClass("datepicker-inline").appendTo(this.element) : this.picker.addClass("datepicker-dropdown dropdown-menu"),
                this.o.rtl && this.picker.addClass("datepicker-rtl"),
                this.o.calendarWeeks &&
                    this.picker.find(".datepicker-days .datepicker-switch, thead .datepicker-title, tfoot .today, tfoot .clear").attr("colspan", function (t, e) {
                        return Number(e) + 1;
                    }),
                this._process_options({ startDate: this._o.startDate, endDate: this._o.endDate, daysOfWeekDisabled: this.o.daysOfWeekDisabled, daysOfWeekHighlighted: this.o.daysOfWeekHighlighted, datesDisabled: this.o.datesDisabled }),
                (this._allow_update = !1),
                this.setViewMode(this.o.startView),
                (this._allow_update = !0),
                this.fillDow(),
                this.fillMonths(),
                this.update(),
                this.isInline && this.show();
        }
        var e,
            i =
                ((e = {
                    get: function (t) {
                        return this.slice(t)[0];
                    },
                    contains: function (t) {
                        for (var e = t && t.valueOf(), i = 0, n = this.length; i < n; i++) if (0 <= this[i].valueOf() - e && this[i].valueOf() - e < 864e5) return i;
                        return -1;
                    },
                    remove: function (t) {
                        this.splice(t, 1);
                    },
                    replace: function (t) {
                        t && (E.isArray(t) || (t = [t]), this.clear(), this.push.apply(this, t));
                    },
                    clear: function () {
                        this.length = 0;
                    },
                    copy: function () {
                        var t = new i();
                        return t.replace(this), t;
                    },
                }),
                function () {
                    var t = [];
                    return t.push.apply(t, arguments), E.extend(t, e), t;
                });
        _.prototype = {
            constructor: _,
            _resolveViewName: function (i) {
                return (
                    E.each(z.viewModes, function (t, e) {
                        if (i === t || -1 !== E.inArray(i, e.names)) return (i = t), !1;
                    }),
                    i
                );
            },
            _resolveDaysOfWeek: function (t) {
                return E.isArray(t) || (t = t.split(/[,\s]*/)), E.map(t, Number);
            },
            _check_template: function (t) {
                try {
                    return t === A || "" === t ? !1 : (t.match(/[<>]/g) || []).length <= 0 || 0 < E(t).length;
                } catch (t) {
                    return !1;
                }
            },
            _process_options: function (t) {
                this._o = E.extend({}, this._o, t);
                var e = (this.o = E.extend({}, this._o)),
                    i = e.language;
                I[i] || ((i = i.split("-")[0]), I[i] || (i = h.language)),
                    (e.language = i),
                    (e.startView = this._resolveViewName(e.startView)),
                    (e.minViewMode = this._resolveViewName(e.minViewMode)),
                    (e.maxViewMode = this._resolveViewName(e.maxViewMode)),
                    (e.startView = Math.max(this.o.minViewMode, Math.min(this.o.maxViewMode, e.startView))),
                    !0 !== e.multidate && ((e.multidate = Number(e.multidate) || !1), !1 !== e.multidate && (e.multidate = Math.max(0, e.multidate))),
                    (e.multidateSeparator = String(e.multidateSeparator)),
                    (e.weekStart %= 7),
                    (e.weekEnd = (e.weekStart + 6) % 7);
                var n = z.parseFormat(e.format);
                e.startDate !== -1 / 0 &&
                    (e.startDate ? (e.startDate instanceof Date ? (e.startDate = this._local_to_utc(this._zero_time(e.startDate))) : (e.startDate = z.parseDate(e.startDate, n, e.language, e.assumeNearbyYear))) : (e.startDate = -1 / 0)),
                    e.endDate !== 1 / 0 &&
                        (e.endDate ? (e.endDate instanceof Date ? (e.endDate = this._local_to_utc(this._zero_time(e.endDate))) : (e.endDate = z.parseDate(e.endDate, n, e.language, e.assumeNearbyYear))) : (e.endDate = 1 / 0)),
                    (e.daysOfWeekDisabled = this._resolveDaysOfWeek(e.daysOfWeekDisabled || [])),
                    (e.daysOfWeekHighlighted = this._resolveDaysOfWeek(e.daysOfWeekHighlighted || [])),
                    (e.datesDisabled = e.datesDisabled || []),
                    E.isArray(e.datesDisabled) || (e.datesDisabled = e.datesDisabled.split(",")),
                    (e.datesDisabled = E.map(e.datesDisabled, function (t) {
                        return z.parseDate(t, n, e.language, e.assumeNearbyYear);
                    }));
                var s = String(e.orientation).toLowerCase().split(/\s+/g),
                    o = e.orientation.toLowerCase();
                if (
                    ((s = E.grep(s, function (t) {
                        return /^auto|left|right|top|bottom$/.test(t);
                    })),
                    (e.orientation = { x: "auto", y: "auto" }),
                    o && "auto" !== o)
                )
                    if (1 === s.length)
                        switch (s[0]) {
                            case "top":
                            case "bottom":
                                e.orientation.y = s[0];
                                break;
                            case "left":
                            case "right":
                                e.orientation.x = s[0];
                        }
                    else
                        (o = E.grep(s, function (t) {
                            return /^left|right$/.test(t);
                        })),
                            (e.orientation.x = o[0] || "auto"),
                            (o = E.grep(s, function (t) {
                                return /^top|bottom$/.test(t);
                            })),
                            (e.orientation.y = o[0] || "auto");
                else;
                if (e.defaultViewDate instanceof Date || "string" === typeof e.defaultViewDate) e.defaultViewDate = z.parseDate(e.defaultViewDate, n, e.language, e.assumeNearbyYear);
                else if (e.defaultViewDate) {
                    var r = e.defaultViewDate.year || new Date().getFullYear(),
                        a = e.defaultViewDate.month || 0,
                        l = e.defaultViewDate.day || 1;
                    e.defaultViewDate = P(r, a, l);
                } else e.defaultViewDate = $();
            },
            _applyEvents: function (t) {
                for (var e, i, n, s = 0; s < t.length; s++) (e = t[s][0]), 2 === t[s].length ? ((i = A), (n = t[s][1])) : 3 === t[s].length && ((i = t[s][1]), (n = t[s][2])), e.on(n, i);
            },
            _unapplyEvents: function (t) {
                for (var e, i, n, s = 0; s < t.length; s++) (e = t[s][0]), 2 === t[s].length ? ((n = A), (i = t[s][1])) : 3 === t[s].length && ((n = t[s][1]), (i = t[s][2])), e.off(i, n);
            },
            _buildEvents: function () {
                var t = {
                    keyup: E.proxy(function (t) {
                        -1 === E.inArray(t.keyCode, [27, 37, 39, 38, 40, 32, 13, 9]) && this.update();
                    }, this),
                    keydown: E.proxy(this.keydown, this),
                    paste: E.proxy(this.paste, this),
                };
                !0 === this.o.showOnFocus && (t.focus = E.proxy(this.show, this)),
                    this.isInput
                        ? (this._events = [[this.element, t]])
                        : this.component && this.inputField.length
                        ? (this._events = [
                              [this.inputField, t],
                              [this.component, { click: E.proxy(this.show, this) }],
                          ])
                        : (this._events = [[this.element, { click: E.proxy(this.show, this), keydown: E.proxy(this.keydown, this) }]]),
                    this._events.push(
                        [
                            this.element,
                            "*",
                            {
                                blur: E.proxy(function (t) {
                                    this._focused_from = t.target;
                                }, this),
                            },
                        ],
                        [
                            this.element,
                            {
                                blur: E.proxy(function (t) {
                                    this._focused_from = t.target;
                                }, this),
                            },
                        ]
                    ),
                    this.o.immediateUpdates &&
                        this._events.push([
                            this.element,
                            {
                                "changeYear changeMonth": E.proxy(function (t) {
                                    this.update(t.date);
                                }, this),
                            },
                        ]),
                    (this._secondaryEvents = [
                        [this.picker, { click: E.proxy(this.click, this) }],
                        [this.picker, ".prev, .next", { click: E.proxy(this.navArrowsClick, this) }],
                        [this.picker, ".day:not(.disabled)", { click: E.proxy(this.dayCellClick, this) }],
                        [E(window), { resize: E.proxy(this.place, this) }],
                        [
                            E(document),
                            {
                                "mousedown touchstart": E.proxy(function (t) {
                                    this.element.is(t.target) || this.element.find(t.target).length || this.picker.is(t.target) || this.picker.find(t.target).length || this.isInline || this.hide();
                                }, this),
                            },
                        ],
                    ]);
            },
            _attachEvents: function () {
                this._detachEvents(), this._applyEvents(this._events);
            },
            _detachEvents: function () {
                this._unapplyEvents(this._events);
            },
            _attachSecondaryEvents: function () {
                this._detachSecondaryEvents(), this._applyEvents(this._secondaryEvents);
            },
            _detachSecondaryEvents: function () {
                this._unapplyEvents(this._secondaryEvents);
            },
            _trigger: function (t, e) {
                var i = e || this.dates.get(-1),
                    n = this._utc_to_local(i);
                this.element.trigger({
                    type: t,
                    date: n,
                    viewMode: this.viewMode,
                    dates: E.map(this.dates, this._utc_to_local),
                    format: E.proxy(function (t, e) {
                        0 === arguments.length ? ((t = this.dates.length - 1), (e = this.o.format)) : "string" === typeof t && ((e = t), (t = this.dates.length - 1)), (e = e || this.o.format);
                        var i = this.dates.get(t);
                        return z.formatDate(i, e, this.o.language);
                    }, this),
                });
            },
            show: function () {
                if (!(this.inputField.is(":disabled") || (this.inputField.prop("readonly") && !1 === this.o.enableOnReadonly)))
                    return (
                        this.isInline || this.picker.appendTo(this.o.container),
                        this.place(),
                        this.picker.show(),
                        this._attachSecondaryEvents(),
                        this._trigger("show"),
                        (window.navigator.msMaxTouchPoints || "ontouchstart" in document) && this.o.disableTouchKeyboard && E(this.element).blur(),
                        this
                    );
            },
            hide: function () {
                return (
                    this.isInline ||
                        !this.picker.is(":visible") ||
                        ((this.focusDate = null), this.picker.hide().detach(), this._detachSecondaryEvents(), this.setViewMode(this.o.startView), this.o.forceParse && this.inputField.val() && this.setValue(), this._trigger("hide")),
                    this
                );
            },
            destroy: function () {
                return this.hide(), this._detachEvents(), this._detachSecondaryEvents(), this.picker.remove(), delete this.element.data().datepicker, this.isInput || delete this.element.data().date, this;
            },
            paste: function (t) {
                var e;
                if (t.originalEvent.clipboardData && t.originalEvent.clipboardData.types && -1 !== E.inArray("text/plain", t.originalEvent.clipboardData.types)) e = t.originalEvent.clipboardData.getData("text/plain");
                else {
                    if (!window.clipboardData) return;
                    e = window.clipboardData.getData("Text");
                }
                this.setDate(e), this.update(), t.preventDefault();
            },
            _utc_to_local: function (t) {
                if (!t) return t;
                var e = new Date(t.getTime() + 6e4 * t.getTimezoneOffset());
                return e.getTimezoneOffset() !== t.getTimezoneOffset() && (e = new Date(t.getTime() + 6e4 * e.getTimezoneOffset())), e;
            },
            _local_to_utc: function (t) {
                return t && new Date(t.getTime() - 6e4 * t.getTimezoneOffset());
            },
            _zero_time: function (t) {
                return t && new Date(t.getFullYear(), t.getMonth(), t.getDate());
            },
            _zero_utc_time: function (t) {
                return t && P(t.getUTCFullYear(), t.getUTCMonth(), t.getUTCDate());
            },
            getDates: function () {
                return E.map(this.dates, this._utc_to_local);
            },
            getUTCDates: function () {
                return E.map(this.dates, function (t) {
                    return new Date(t);
                });
            },
            getDate: function () {
                return this._utc_to_local(this.getUTCDate());
            },
            getUTCDate: function () {
                var t = this.dates.get(-1);
                return t !== A ? new Date(t) : null;
            },
            clearDates: function () {
                this.inputField.val(""), this.update(), this._trigger("changeDate"), this.o.autoclose && this.hide();
            },
            setDates: function () {
                var t = E.isArray(arguments[0]) ? arguments[0] : arguments;
                return this.update.apply(this, t), this._trigger("changeDate"), this.setValue(), this;
            },
            setUTCDates: function () {
                var t = E.isArray(arguments[0]) ? arguments[0] : arguments;
                return this.setDates.apply(this, E.map(t, this._utc_to_local)), this;
            },
            setDate: t("setDates"),
            setUTCDate: t("setUTCDates"),
            remove: t("destroy", "Method `remove` is deprecated and will be removed in version 2.0. Use `destroy` instead"),
            setValue: function () {
                var t = this.getFormattedDate();
                return this.inputField.val(t), this;
            },
            getFormattedDate: function (e) {
                e === A && (e = this.o.format);
                var i = this.o.language;
                return E.map(this.dates, function (t) {
                    return z.formatDate(t, e, i);
                }).join(this.o.multidateSeparator);
            },
            getStartDate: function () {
                return this.o.startDate;
            },
            setStartDate: function (t) {
                return this._process_options({ startDate: t }), this.update(), this.updateNavArrows(), this;
            },
            getEndDate: function () {
                return this.o.endDate;
            },
            setEndDate: function (t) {
                return this._process_options({ endDate: t }), this.update(), this.updateNavArrows(), this;
            },
            setDaysOfWeekDisabled: function (t) {
                return this._process_options({ daysOfWeekDisabled: t }), this.update(), this;
            },
            setDaysOfWeekHighlighted: function (t) {
                return this._process_options({ daysOfWeekHighlighted: t }), this.update(), this;
            },
            setDatesDisabled: function (t) {
                return this._process_options({ datesDisabled: t }), this.update(), this;
            },
            place: function () {
                if (this.isInline) return this;
                var t = this.picker.outerWidth(),
                    e = this.picker.outerHeight(),
                    i = E(this.o.container),
                    n = i.width(),
                    s = "body" === this.o.container ? E(document).scrollTop() : i.scrollTop(),
                    o = i.offset(),
                    r = [0];
                this.element.parents().each(function () {
                    var t = E(this).css("z-index");
                    "auto" !== t && 0 !== Number(t) && r.push(Number(t));
                });
                var a = Math.max.apply(Math, r) + this.o.zIndexOffset,
                    l = this.component ? this.component.parent().offset() : this.element.offset(),
                    c = this.component ? this.component.outerHeight(!0) : this.element.outerHeight(!1),
                    h = this.component ? this.component.outerWidth(!0) : this.element.outerWidth(!1),
                    u = l.left - o.left,
                    d = l.top - o.top;
                "body" !== this.o.container && (d += s),
                    this.picker.removeClass("datepicker-orient-top datepicker-orient-bottom datepicker-orient-right datepicker-orient-left"),
                    "auto" !== this.o.orientation.x
                        ? (this.picker.addClass("datepicker-orient-" + this.o.orientation.x), "right" === this.o.orientation.x && (u -= t - h))
                        : l.left < 0
                        ? (this.picker.addClass("datepicker-orient-left"), (u -= l.left - 10))
                        : n < u + t
                        ? (this.picker.addClass("datepicker-orient-right"), (u += h - t))
                        : this.o.rtl
                        ? this.picker.addClass("datepicker-orient-right")
                        : this.picker.addClass("datepicker-orient-left");
                var p = this.o.orientation.y;
                if (("auto" === p && (p = -s + d - e < 0 ? "bottom" : "top"), this.picker.addClass("datepicker-orient-" + p), "top" === p ? (d -= e + parseInt(this.picker.css("padding-top"))) : (d += c), this.o.rtl)) {
                    var f = n - (u + h);
                    this.picker.css({ top: d, right: f, zIndex: a });
                } else this.picker.css({ top: d, left: u, zIndex: a });
                return this;
            },
            _allow_update: !0,
            update: function () {
                if (!this._allow_update) return this;
                var t = this.dates.copy(),
                    i = [],
                    e = !1;
                return (
                    arguments.length
                        ? (E.each(
                              arguments,
                              E.proxy(function (t, e) {
                                  e instanceof Date && (e = this._local_to_utc(e)), i.push(e);
                              }, this)
                          ),
                          (e = !0))
                        : ((i = (i = this.isInput ? this.element.val() : this.element.data("date") || this.inputField.val()) && this.o.multidate ? i.split(this.o.multidateSeparator) : [i]), delete this.element.data().date),
                    (i = E.map(
                        i,
                        E.proxy(function (t) {
                            return z.parseDate(t, this.o.format, this.o.language, this.o.assumeNearbyYear);
                        }, this)
                    )),
                    (i = E.grep(
                        i,
                        E.proxy(function (t) {
                            return !this.dateWithinRange(t) || !t;
                        }, this),
                        !0
                    )),
                    this.dates.replace(i),
                    this.o.updateViewDate &&
                        (this.dates.length
                            ? (this.viewDate = new Date(this.dates.get(-1)))
                            : this.viewDate < this.o.startDate
                            ? (this.viewDate = new Date(this.o.startDate))
                            : this.viewDate > this.o.endDate
                            ? (this.viewDate = new Date(this.o.endDate))
                            : (this.viewDate = this.o.defaultViewDate)),
                    e ? (this.setValue(), this.element.change()) : this.dates.length && String(t) !== String(this.dates) && e && (this._trigger("changeDate"), this.element.change()),
                    !this.dates.length && t.length && (this._trigger("clearDate"), this.element.change()),
                    this.fill(),
                    this
                );
            },
            fillDow: function () {
                if (this.o.showWeekDays) {
                    var t = this.o.weekStart,
                        e = "<tr>";
                    for (this.o.calendarWeeks && (e += '<th class="cw">&#160;</th>'); t < this.o.weekStart + 7; )
                        (e += '<th class="dow'), -1 !== E.inArray(t, this.o.daysOfWeekDisabled) && (e += " disabled"), (e += '">' + I[this.o.language].daysMin[t++ % 7] + "</th>");
                    (e += "</tr>"), this.picker.find(".datepicker-days thead").append(e);
                }
            },
            fillMonths: function () {
                for (var t = this._utc_to_local(this.viewDate), e = "", i = 0; i < 12; i++) e += '<span class="month' + (t && t.getMonth() === i ? " focused" : "") + '">' + I[this.o.language].monthsShort[i] + "</span>";
                this.picker.find(".datepicker-months td").html(e);
            },
            setRange: function (t) {
                t && t.length
                    ? (this.range = E.map(t, function (t) {
                          return t.valueOf();
                      }))
                    : delete this.range,
                    this.fill();
            },
            getClassNames: function (t) {
                var e = [],
                    i = this.viewDate.getUTCFullYear(),
                    n = this.viewDate.getUTCMonth(),
                    s = $();
                return (
                    t.getUTCFullYear() < i || (t.getUTCFullYear() === i && t.getUTCMonth() < n) ? e.push("old") : (t.getUTCFullYear() > i || (t.getUTCFullYear() === i && t.getUTCMonth() > n)) && e.push("new"),
                    this.focusDate && t.valueOf() === this.focusDate.valueOf() && e.push("focused"),
                    this.o.todayHighlight && o(t, s) && e.push("today"),
                    -1 !== this.dates.contains(t) && e.push("active"),
                    this.dateWithinRange(t) || e.push("disabled"),
                    this.dateIsDisabled(t) && e.push("disabled", "disabled-date"),
                    -1 !== E.inArray(t.getUTCDay(), this.o.daysOfWeekHighlighted) && e.push("highlighted"),
                    this.range &&
                        (t > this.range[0] && t < this.range[this.range.length - 1] && e.push("range"),
                        -1 !== E.inArray(t.valueOf(), this.range) && e.push("selected"),
                        t.valueOf() === this.range[0] && e.push("range-start"),
                        t.valueOf() === this.range[this.range.length - 1] && e.push("range-end")),
                    e
                );
            },
            _fill_yearsView: function (t, e, i, n, s, o, r) {
                for (
                    var a,
                        l,
                        c,
                        h = "",
                        u = i / 10,
                        d = this.picker.find(t),
                        p = Math.floor(n / i) * i,
                        f = p + 9 * u,
                        g = Math.floor(this.viewDate.getFullYear() / u) * u,
                        m = E.map(this.dates, function (t) {
                            return Math.floor(t.getUTCFullYear() / u) * u;
                        }),
                        v = p - u;
                    v <= f + u;
                    v += u
                )
                    (a = [e]),
                        (l = null),
                        v === p - u ? a.push("old") : v === f + u && a.push("new"),
                        -1 !== E.inArray(v, m) && a.push("active"),
                        (v < s || o < v) && a.push("disabled"),
                        v === g && a.push("focused"),
                        r !== E.noop &&
                            ((c = r(new Date(v, 0, 1))) === A ? (c = {}) : "boolean" === typeof c ? (c = { enabled: c }) : "string" === typeof c && (c = { classes: c }),
                            !1 === c.enabled && a.push("disabled"),
                            c.classes && (a = a.concat(c.classes.split(/\s+/))),
                            c.tooltip && (l = c.tooltip)),
                        (h += '<span class="' + a.join(" ") + '"' + (l ? ' title="' + l + '"' : "") + ">" + v + "</span>");
                d.find(".datepicker-switch").text(p + "-" + f), d.find("td").html(h);
            },
            fill: function () {
                var t,
                    e,
                    i = new Date(this.viewDate),
                    s = i.getUTCFullYear(),
                    n = i.getUTCMonth(),
                    o = this.o.startDate !== -1 / 0 ? this.o.startDate.getUTCFullYear() : -1 / 0,
                    r = this.o.startDate !== -1 / 0 ? this.o.startDate.getUTCMonth() : -1 / 0,
                    a = this.o.endDate !== 1 / 0 ? this.o.endDate.getUTCFullYear() : 1 / 0,
                    l = this.o.endDate !== 1 / 0 ? this.o.endDate.getUTCMonth() : 1 / 0,
                    c = I[this.o.language].today || I.en.today || "",
                    h = I[this.o.language].clear || I.en.clear || "",
                    u = I[this.o.language].titleFormat || I.en.titleFormat,
                    d = $(),
                    p = (!0 === this.o.todayBtn || "linked" === this.o.todayBtn) && d >= this.o.startDate && d <= this.o.endDate && !this.weekOfDateIsDisabled(d);
                if (!isNaN(s) && !isNaN(n)) {
                    this.picker.find(".datepicker-days .datepicker-switch").text(z.formatDate(i, u, this.o.language)),
                        this.picker
                            .find("tfoot .today")
                            .text(c)
                            .css("display", p ? "table-cell" : "none"),
                        this.picker
                            .find("tfoot .clear")
                            .text(h)
                            .css("display", !0 === this.o.clearBtn ? "table-cell" : "none"),
                        this.picker
                            .find("thead .datepicker-title")
                            .text(this.o.title)
                            .css("display", "string" === typeof this.o.title && "" !== this.o.title ? "table-cell" : "none"),
                        this.updateNavArrows(),
                        this.fillMonths();
                    var f = P(s, n, 0),
                        g = f.getUTCDate();
                    f.setUTCDate(g - ((f.getUTCDay() - this.o.weekStart + 7) % 7));
                    var m = new Date(f);
                    f.getUTCFullYear() < 100 && m.setUTCFullYear(f.getUTCFullYear()), m.setUTCDate(m.getUTCDate() + 42), (m = m.valueOf());
                    for (var v, y, b = []; f.valueOf() < m; ) {
                        if ((v = f.getUTCDay()) === this.o.weekStart && (b.push("<tr>"), this.o.calendarWeeks)) {
                            var w = new Date(+f + ((this.o.weekStart - v - 7) % 7) * 864e5),
                                _ = new Date(Number(w) + ((11 - w.getUTCDay()) % 7) * 864e5),
                                x = new Date(Number((x = P(_.getUTCFullYear(), 0, 1))) + ((11 - x.getUTCDay()) % 7) * 864e5),
                                C = (_ - x) / 864e5 / 7 + 1;
                            b.push('<td class="cw">' + C + "</td>");
                        }
                        (y = this.getClassNames(f)).push("day");
                        var D = f.getUTCDate();
                        this.o.beforeShowDay !== E.noop &&
                            ((e = this.o.beforeShowDay(this._utc_to_local(f))) === A ? (e = {}) : "boolean" === typeof e ? (e = { enabled: e }) : "string" === typeof e && (e = { classes: e }),
                            !1 === e.enabled && y.push("disabled"),
                            e.classes && (y = y.concat(e.classes.split(/\s+/))),
                            e.tooltip && (t = e.tooltip),
                            e.content && (D = e.content)),
                            (y = E.isFunction(E.uniqueSort) ? E.uniqueSort(y) : E.unique(y)),
                            b.push('<td class="' + y.join(" ") + '"' + (t ? ' title="' + t + '"' : "") + ' data-date="' + f.getTime().toString() + '">' + D + "</td>"),
                            (t = null),
                            v === this.o.weekEnd && b.push("</tr>"),
                            f.setUTCDate(f.getUTCDate() + 1);
                    }
                    this.picker.find(".datepicker-days tbody").html(b.join(""));
                    var k = I[this.o.language].monthsTitle || I.en.monthsTitle || "Months",
                        T = this.picker
                            .find(".datepicker-months")
                            .find(".datepicker-switch")
                            .text(this.o.maxViewMode < 2 ? k : s)
                            .end()
                            .find("tbody span")
                            .removeClass("active");
                    if (
                        (E.each(this.dates, function (t, e) {
                            e.getUTCFullYear() === s && T.eq(e.getUTCMonth()).addClass("active");
                        }),
                        (s < o || a < s) && T.addClass("disabled"),
                        s === o && T.slice(0, r).addClass("disabled"),
                        s === a && T.slice(l + 1).addClass("disabled"),
                        this.o.beforeShowMonth !== E.noop)
                    ) {
                        var S = this;
                        E.each(T, function (t, e) {
                            var i = new Date(s, t, 1),
                                n = S.o.beforeShowMonth(i);
                            n === A ? (n = {}) : "boolean" === typeof n ? (n = { enabled: n }) : "string" === typeof n && (n = { classes: n }),
                                !1 !== n.enabled || E(e).hasClass("disabled") || E(e).addClass("disabled"),
                                n.classes && E(e).addClass(n.classes),
                                n.tooltip && E(e).prop("title", n.tooltip);
                        });
                    }
                    this._fill_yearsView(".datepicker-years", "year", 10, s, o, a, this.o.beforeShowYear),
                        this._fill_yearsView(".datepicker-decades", "decade", 100, s, o, a, this.o.beforeShowDecade),
                        this._fill_yearsView(".datepicker-centuries", "century", 1e3, s, o, a, this.o.beforeShowCentury);
                }
            },
            updateNavArrows: function () {
                if (this._allow_update) {
                    var t,
                        e,
                        i = new Date(this.viewDate),
                        n = i.getUTCFullYear(),
                        s = i.getUTCMonth(),
                        o = this.o.startDate !== -1 / 0 ? this.o.startDate.getUTCFullYear() : -1 / 0,
                        r = this.o.startDate !== -1 / 0 ? this.o.startDate.getUTCMonth() : -1 / 0,
                        a = this.o.endDate !== 1 / 0 ? this.o.endDate.getUTCFullYear() : 1 / 0,
                        l = this.o.endDate !== 1 / 0 ? this.o.endDate.getUTCMonth() : 1 / 0,
                        c = 1;
                    switch (this.viewMode) {
                        case 4:
                            c *= 10;
                        case 3:
                            c *= 10;
                        case 2:
                            c *= 10;
                        case 1:
                            (t = Math.floor(n / c) * c <= o), (e = Math.floor(n / c) * c + c > a);
                            break;
                        case 0:
                            (t = n <= o && s <= r), (e = a <= n && l <= s);
                    }
                    this.picker.find(".prev").toggleClass("disabled", t), this.picker.find(".next").toggleClass("disabled", e);
                }
            },
            click: function (t) {
                var e, i, n;
                t.preventDefault(),
                    t.stopPropagation(),
                    (e = E(t.target)).hasClass("datepicker-switch") && this.viewMode !== this.o.maxViewMode && this.setViewMode(this.viewMode + 1),
                    e.hasClass("today") && !e.hasClass("day") && (this.setViewMode(0), this._setDate($(), "linked" === this.o.todayBtn ? null : "view")),
                    e.hasClass("clear") && this.clearDates(),
                    e.hasClass("disabled") ||
                        ((e.hasClass("month") || e.hasClass("year") || e.hasClass("decade") || e.hasClass("century")) &&
                            (this.viewDate.setUTCDate(1),
                            1 === this.viewMode ? ((n = e.parent().find("span").index(e)), (i = this.viewDate.getUTCFullYear()), this.viewDate.setUTCMonth(n)) : ((n = 0), (i = Number(e.text())), this.viewDate.setUTCFullYear(i)),
                            this._trigger(z.viewModes[this.viewMode - 1].e, this.viewDate),
                            this.viewMode === this.o.minViewMode ? this._setDate(P(i, n, 1)) : (this.setViewMode(this.viewMode - 1), this.fill()))),
                    this.picker.is(":visible") && this._focused_from && this._focused_from.focus(),
                    delete this._focused_from;
            },
            dayCellClick: function (t) {
                var e = E(t.currentTarget).data("date"),
                    i = new Date(e);
                this.o.updateViewDate && (i.getUTCFullYear() !== this.viewDate.getUTCFullYear() && this._trigger("changeYear", this.viewDate), i.getUTCMonth() !== this.viewDate.getUTCMonth() && this._trigger("changeMonth", this.viewDate)),
                    this._setDate(i);
            },
            navArrowsClick: function (t) {
                var e = E(t.currentTarget).hasClass("prev") ? -1 : 1;
                0 !== this.viewMode && (e *= 12 * z.viewModes[this.viewMode].navStep), (this.viewDate = this.moveMonth(this.viewDate, e)), this._trigger(z.viewModes[this.viewMode].e, this.viewDate), this.fill();
            },
            _toggle_multidate: function (t) {
                var e = this.dates.contains(t);
                if (
                    (t || this.dates.clear(),
                    -1 !== e ? (!0 === this.o.multidate || 1 < this.o.multidate || this.o.toggleActive) && this.dates.remove(e) : (!1 === this.o.multidate && this.dates.clear(), this.dates.push(t)),
                    "number" === typeof this.o.multidate)
                )
                    for (; this.dates.length > this.o.multidate; ) this.dates.remove(0);
            },
            _setDate: function (t, e) {
                (e && "date" !== e) || this._toggle_multidate(t && new Date(t)),
                    ((!e && this.o.updateViewDate) || "view" === e) && (this.viewDate = t && new Date(t)),
                    this.fill(),
                    this.setValue(),
                    (e && "view" === e) || this._trigger("changeDate"),
                    this.inputField.trigger("change"),
                    !this.o.autoclose || (e && "date" !== e) || this.hide();
            },
            moveDay: function (t, e) {
                var i = new Date(t);
                return i.setUTCDate(t.getUTCDate() + e), i;
            },
            moveWeek: function (t, e) {
                return this.moveDay(t, 7 * e);
            },
            moveMonth: function (t, e) {
                if (!(i = t) || isNaN(i.getTime())) return this.o.defaultViewDate;
                var i;
                if (!e) return t;
                var n,
                    s,
                    o = new Date(t.valueOf()),
                    r = o.getUTCDate(),
                    a = o.getUTCMonth(),
                    l = Math.abs(e);
                if (((e = 0 < e ? 1 : -1), 1 === l))
                    (s =
                        -1 === e
                            ? function () {
                                  return o.getUTCMonth() === a;
                              }
                            : function () {
                                  return o.getUTCMonth() !== n;
                              }),
                        (n = a + e),
                        o.setUTCMonth(n),
                        (n = (n + 12) % 12);
                else {
                    for (var c = 0; c < l; c++) o = this.moveMonth(o, e);
                    (n = o.getUTCMonth()),
                        o.setUTCDate(r),
                        (s = function () {
                            return n !== o.getUTCMonth();
                        });
                }
                for (; s(); ) o.setUTCDate(--r), o.setUTCMonth(n);
                return o;
            },
            moveYear: function (t, e) {
                return this.moveMonth(t, 12 * e);
            },
            moveAvailableDate: function (t, e, i) {
                do {
                    if (((t = this[i](t, e)), !this.dateWithinRange(t))) return !1;
                    i = "moveDay";
                } while (this.dateIsDisabled(t));
                return t;
            },
            weekOfDateIsDisabled: function (t) {
                return -1 !== E.inArray(t.getUTCDay(), this.o.daysOfWeekDisabled);
            },
            dateIsDisabled: function (e) {
                return (
                    this.weekOfDateIsDisabled(e) ||
                    0 <
                        E.grep(this.o.datesDisabled, function (t) {
                            return o(e, t);
                        }).length
                );
            },
            dateWithinRange: function (t) {
                return t >= this.o.startDate && t <= this.o.endDate;
            },
            keydown: function (t) {
                if (this.picker.is(":visible")) {
                    var e,
                        i,
                        n = !1,
                        s = this.focusDate || this.viewDate;
                    switch (t.keyCode) {
                        case 27:
                            this.focusDate ? ((this.focusDate = null), (this.viewDate = this.dates.get(-1) || this.viewDate), this.fill()) : this.hide(), t.preventDefault(), t.stopPropagation();
                            break;
                        case 37:
                        case 38:
                        case 39:
                        case 40:
                            if (!this.o.keyboardNavigation || 7 === this.o.daysOfWeekDisabled.length) break;
                            (e = 37 === t.keyCode || 38 === t.keyCode ? -1 : 1),
                                0 === this.viewMode
                                    ? t.ctrlKey
                                        ? (i = this.moveAvailableDate(s, e, "moveYear")) && this._trigger("changeYear", this.viewDate)
                                        : t.shiftKey
                                        ? (i = this.moveAvailableDate(s, e, "moveMonth")) && this._trigger("changeMonth", this.viewDate)
                                        : 37 === t.keyCode || 39 === t.keyCode
                                        ? (i = this.moveAvailableDate(s, e, "moveDay"))
                                        : this.weekOfDateIsDisabled(s) || (i = this.moveAvailableDate(s, e, "moveWeek"))
                                    : 1 === this.viewMode
                                    ? ((38 !== t.keyCode && 40 !== t.keyCode) || (e *= 4), (i = this.moveAvailableDate(s, e, "moveMonth")))
                                    : 2 === this.viewMode && ((38 !== t.keyCode && 40 !== t.keyCode) || (e *= 4), (i = this.moveAvailableDate(s, e, "moveYear"))),
                                i && ((this.focusDate = this.viewDate = i), this.setValue(), this.fill(), t.preventDefault());
                            break;
                        case 13:
                            if (!this.o.forceParse) break;
                            (s = this.focusDate || this.dates.get(-1) || this.viewDate),
                                this.o.keyboardNavigation && (this._toggle_multidate(s), (n = !0)),
                                (this.focusDate = null),
                                (this.viewDate = this.dates.get(-1) || this.viewDate),
                                this.setValue(),
                                this.fill(),
                                this.picker.is(":visible") && (t.preventDefault(), t.stopPropagation(), this.o.autoclose && this.hide());
                            break;
                        case 9:
                            (this.focusDate = null), (this.viewDate = this.dates.get(-1) || this.viewDate), this.fill(), this.hide();
                    }
                    n && (this.dates.length ? this._trigger("changeDate") : this._trigger("clearDate"), this.inputField.trigger("change"));
                } else (40 !== t.keyCode && 27 !== t.keyCode) || (this.show(), t.stopPropagation());
            },
            setViewMode: function (t) {
                (this.viewMode = t),
                    this.picker
                        .children("div")
                        .hide()
                        .filter(".datepicker-" + z.viewModes[this.viewMode].clsName)
                        .show(),
                    this.updateNavArrows(),
                    this._trigger("changeViewMode", new Date(this.viewDate));
            },
        };
        function c(t, e) {
            E.data(t, "datepicker", this),
                (this.element = E(t)),
                (this.inputs = E.map(e.inputs, function (t) {
                    return t.jquery ? t[0] : t;
                })),
                delete e.inputs,
                (this.keepEmptyValues = e.keepEmptyValues),
                delete e.keepEmptyValues,
                s.call(E(this.inputs), e).on("changeDate", E.proxy(this.dateUpdated, this)),
                (this.pickers = E.map(this.inputs, function (t) {
                    return E.data(t, "datepicker");
                })),
                this.updateDates();
        }
        c.prototype = {
            updateDates: function () {
                (this.dates = E.map(this.pickers, function (t) {
                    return t.getUTCDate();
                })),
                    this.updateRanges();
            },
            updateRanges: function () {
                var i = E.map(this.dates, function (t) {
                    return t.valueOf();
                });
                E.each(this.pickers, function (t, e) {
                    e.setRange(i);
                });
            },
            clearDates: function () {
                E.each(this.pickers, function (t, e) {
                    e.clearDates();
                });
            },
            dateUpdated: function (t) {
                if (!this.updating) {
                    this.updating = !0;
                    var i = E.data(t.target, "datepicker");
                    if (i !== A) {
                        var n = i.getUTCDate(),
                            s = this.keepEmptyValues,
                            e = E.inArray(t.target, this.inputs),
                            o = e - 1,
                            r = e + 1,
                            a = this.inputs.length;
                        if (-1 !== e) {
                            if (
                                (E.each(this.pickers, function (t, e) {
                                    e.getUTCDate() || (e !== i && s) || e.setUTCDate(n);
                                }),
                                n < this.dates[o])
                            )
                                for (; 0 <= o && n < this.dates[o]; ) this.pickers[o--].setUTCDate(n);
                            else if (n > this.dates[r]) for (; r < a && n > this.dates[r]; ) this.pickers[r++].setUTCDate(n);
                            this.updateDates(), delete this.updating;
                        }
                    }
                }
            },
            destroy: function () {
                E.map(this.pickers, function (t) {
                    t.destroy();
                }),
                    E(this.inputs).off("changeDate", this.dateUpdated),
                    delete this.element.data().datepicker;
            },
            remove: t("destroy", "Method `remove` is deprecated and will be removed in version 2.0. Use `destroy` instead"),
        };
        var n = E.fn.datepicker,
            s = function (r) {
                var a,
                    l = Array.apply(null, arguments);
                if (
                    (l.shift(),
                    this.each(function () {
                        var t = E(this),
                            e = t.data("datepicker"),
                            i = "object" === typeof r && r;
                        if (!e) {
                            var n = (function (t, e) {
                                    var i = E(t).data(),
                                        n = {},
                                        s = new RegExp("^" + e.toLowerCase() + "([A-Z])");
                                    function o(t, e) {
                                        return e.toLowerCase();
                                    }
                                    for (var r in ((e = new RegExp("^" + e.toLowerCase())), i)) e.test(r) && (n[r.replace(s, o)] = i[r]);
                                    return n;
                                })(this, "date"),
                                s = (function (t) {
                                    var i = {};
                                    if (I[t] || ((t = t.split("-")[0]), I[t])) {
                                        var n = I[t];
                                        return (
                                            E.each(u, function (t, e) {
                                                e in n && (i[e] = n[e]);
                                            }),
                                            i
                                        );
                                    }
                                })(E.extend({}, h, n, i).language),
                                o = E.extend({}, h, s, n, i);
                            (e = t.hasClass("input-daterange") || o.inputs ? (E.extend(o, { inputs: o.inputs || t.find("input").toArray() }), new c(this, o)) : new _(this, o)), t.data("datepicker", e);
                        }
                        "string" === typeof r && "function" === typeof e[r] && (a = e[r].apply(e, l));
                    }),
                    a === A || a instanceof _ || a instanceof c)
                )
                    return this;
                if (1 < this.length) throw new Error("Using only allowed for the collection of a single element (" + r + " function)");
                return a;
            };
        E.fn.datepicker = s;
        var h = (E.fn.datepicker.defaults = {
                assumeNearbyYear: !1,
                autoclose: !1,
                beforeShowDay: E.noop,
                beforeShowMonth: E.noop,
                beforeShowYear: E.noop,
                beforeShowDecade: E.noop,
                beforeShowCentury: E.noop,
                calendarWeeks: !1,
                clearBtn: !1,
                toggleActive: !1,
                daysOfWeekDisabled: [],
                daysOfWeekHighlighted: [],
                datesDisabled: [],
                endDate: 1 / 0,
                forceParse: !0,
                format: "mm/dd/yyyy",
                keepEmptyValues: !1,
                keyboardNavigation: !0,
                language: "en",
                minViewMode: 0,
                maxViewMode: 4,
                multidate: !1,
                multidateSeparator: ",",
                orientation: "auto",
                rtl: !1,
                startDate: -1 / 0,
                startView: 0,
                todayBtn: !1,
                todayHighlight: !1,
                updateViewDate: !0,
                weekStart: 0,
                disableTouchKeyboard: !1,
                enableOnReadonly: !0,
                showOnFocus: !0,
                zIndexOffset: 10,
                container: "body",
                immediateUpdates: !1,
                title: "",
                templates: { leftArrow: "&#x00AB;", rightArrow: "&#x00BB;" },
                showWeekDays: !0,
            }),
            u = (E.fn.datepicker.locale_opts = ["format", "rtl", "weekStart"]);
        E.fn.datepicker.Constructor = _;
        var I = (E.fn.datepicker.dates = {
                en: {
                    days: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
                    daysShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
                    daysMin: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"],
                    months: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
                    monthsShort: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
                    today: "Today",
                    clear: "Clear",
                    titleFormat: "MM yyyy",
                },
            }),
            z = {
                viewModes: [
                    { names: ["days", "month"], clsName: "days", e: "changeMonth" },
                    { names: ["months", "year"], clsName: "months", e: "changeYear", navStep: 1 },
                    { names: ["years", "decade"], clsName: "years", e: "changeDecade", navStep: 10 },
                    { names: ["decades", "century"], clsName: "decades", e: "changeCentury", navStep: 100 },
                    { names: ["centuries", "millennium"], clsName: "centuries", e: "changeMillennium", navStep: 1e3 },
                ],
                validParts: /dd?|DD?|mm?|MM?|yy(?:yy)?/g,
                nonpunctuation: /[^ -\/:-@\u5e74\u6708\u65e5\[-`{-~\t\n\r]+/g,
                parseFormat: function (t) {
                    if ("function" === typeof t.toValue && "function" === typeof t.toDisplay) return t;
                    var e = t.replace(this.validParts, "\0").split("\0"),
                        i = t.match(this.validParts);
                    if (!e || !e.length || !i || 0 === i.length) throw new Error("Invalid date format.");
                    return { separators: e, parts: i };
                },
                parseDate: function (t, e, i, s) {
                    if (!t) return A;
                    if (t instanceof Date) return t;
                    if (("string" === typeof e && (e = z.parseFormat(e)), e.toValue)) return e.toValue(t, e, i);
                    var n,
                        o,
                        r,
                        a,
                        l,
                        c = { d: "moveDay", m: "moveMonth", w: "moveWeek", y: "moveYear" },
                        h = { yesterday: "-1d", today: "+0d", tomorrow: "+1d" };
                    if ((t in h && (t = h[t]), /^[\-+]\d+[dmwy]([\s,]+[\-+]\d+[dmwy])*$/i.test(t))) {
                        for (n = t.match(/([\-+]\d+)([dmwy])/gi), t = new Date(), a = 0; a < n.length; a++) (o = n[a].match(/([\-+]\d+)([dmwy])/i)), (r = Number(o[1])), (l = c[o[2].toLowerCase()]), (t = _.prototype[l](t, r));
                        return _.prototype._zero_utc_time(t);
                    }
                    n = (t && t.match(this.nonpunctuation)) || [];
                    var u,
                        d,
                        p = {},
                        f = ["yyyy", "yy", "M", "MM", "m", "mm", "d", "dd"],
                        g = {
                            yyyy: function (t, e) {
                                return t.setUTCFullYear(s ? (!0 === (n = s) && (n = 10), (i = e) < 100 && (i += 2e3) > new Date().getFullYear() + n && (i -= 100), i) : e);
                                var i, n;
                            },
                            m: function (t, e) {
                                if (isNaN(t)) return t;
                                for (--e; e < 0; ) e += 12;
                                for (e %= 12, t.setUTCMonth(e); t.getUTCMonth() !== e; ) t.setUTCDate(t.getUTCDate() - 1);
                                return t;
                            },
                            d: function (t, e) {
                                return t.setUTCDate(e);
                            },
                        };
                    (g.yy = g.yyyy), (g.M = g.MM = g.mm = g.m), (g.dd = g.d), (t = $());
                    var m = e.parts.slice();
                    function v() {
                        var t = this.slice(0, n[a].length),
                            e = n[a].slice(0, t.length);
                        return t.toLowerCase() === e.toLowerCase();
                    }
                    if (
                        (n.length !== m.length &&
                            (m = E(m)
                                .filter(function (t, e) {
                                    return -1 !== E.inArray(e, f);
                                })
                                .toArray()),
                        n.length === m.length)
                    ) {
                        var y, b, w;
                        for (a = 0, y = m.length; a < y; a++) {
                            if (((u = parseInt(n[a], 10)), (o = m[a]), isNaN(u)))
                                switch (o) {
                                    case "MM":
                                        (d = E(I[i].months).filter(v)), (u = E.inArray(d[0], I[i].months) + 1);
                                        break;
                                    case "M":
                                        (d = E(I[i].monthsShort).filter(v)), (u = E.inArray(d[0], I[i].monthsShort) + 1);
                                }
                            p[o] = u;
                        }
                        for (a = 0; a < f.length; a++) (w = f[a]) in p && !isNaN(p[w]) && ((b = new Date(t)), g[w](b, p[w]), isNaN(b) || (t = b));
                    }
                    return t;
                },
                formatDate: function (t, e, i) {
                    if (!t) return "";
                    if (("string" === typeof e && (e = z.parseFormat(e)), e.toDisplay)) return e.toDisplay(t, e, i);
                    var n = {
                        d: t.getUTCDate(),
                        D: I[i].daysShort[t.getUTCDay()],
                        DD: I[i].days[t.getUTCDay()],
                        m: t.getUTCMonth() + 1,
                        M: I[i].monthsShort[t.getUTCMonth()],
                        MM: I[i].months[t.getUTCMonth()],
                        yy: t.getUTCFullYear().toString().substring(2),
                        yyyy: t.getUTCFullYear(),
                    };
                    (n.dd = (n.d < 10 ? "0" : "") + n.d), (n.mm = (n.m < 10 ? "0" : "") + n.m), (t = []);
                    for (var s = E.extend([], e.separators), o = 0, r = e.parts.length; o <= r; o++) s.length && t.push(s.shift()), t.push(n[e.parts[o]]);
                    return t.join("");
                },
                headTemplate:
                    '<thead><tr><th colspan="7" class="datepicker-title"></th></tr><tr><th class="prev">' +
                    h.templates.leftArrow +
                    '</th><th colspan="5" class="datepicker-switch"></th><th class="next">' +
                    h.templates.rightArrow +
                    "</th></tr></thead>",
                contTemplate: '<tbody><tr><td colspan="7"></td></tr></tbody>',
                footTemplate: '<tfoot><tr><th colspan="7" class="today"></th></tr><tr><th colspan="7" class="clear"></th></tr></tfoot>',
            };
        (z.template =
            '<div class="datepicker"><div class="datepicker-days"><table class="table-condensed">' +
            z.headTemplate +
            "<tbody></tbody>" +
            z.footTemplate +
            '</table></div><div class="datepicker-months"><table class="table-condensed">' +
            z.headTemplate +
            z.contTemplate +
            z.footTemplate +
            '</table></div><div class="datepicker-years"><table class="table-condensed">' +
            z.headTemplate +
            z.contTemplate +
            z.footTemplate +
            '</table></div><div class="datepicker-decades"><table class="table-condensed">' +
            z.headTemplate +
            z.contTemplate +
            z.footTemplate +
            '</table></div><div class="datepicker-centuries"><table class="table-condensed">' +
            z.headTemplate +
            z.contTemplate +
            z.footTemplate +
            "</table></div></div>"),
            (E.fn.datepicker.DPGlobal = z),
            (E.fn.datepicker.noConflict = function () {
                return (E.fn.datepicker = n), this;
            }),
            (E.fn.datepicker.version = "1.9.0"),
            (E.fn.datepicker.deprecated = function (t) {
                var e = window.console;
                e && e.warn && e.warn("DEPRECATED: " + t);
            }),
            E(document).on("focus.datepicker.data-api click.datepicker.data-api", '[data-provide="datepicker"]', function (t) {
                var e = E(this);
                e.data("datepicker") || (t.preventDefault(), s.call(e, "show"));
            }),
            E(function () {
                s.call(E('[data-provide="datepicker-inline"]'));
            });
    }),
    (function (i) {
        "function" === typeof define && define.amd
            ? define(["jquery"], i)
            : "object" === typeof module && module.exports
            ? (module.exports = function (t, e) {
                  return void 0 === e && (e = "undefined" !==    typeof window ? require("jquery") : require("jquery")(t)), i(e), e;
              })
            : i(jQuery);
    })(function (u) {
        var t = (function () {
                if (u && u.fn && u.fn.select2 && u.fn.select2.amd) var t = u.fn.select2.amd;
                var e, i, n, p, o, r, f, g, m, v, y, b, s, a, w, l;
                function _(t, e) {
                    return s.call(t, e);
                }
                function c(t, e) {
                    var i,
                        n,
                        s,
                        o,
                        r,
                        a,
                        l,
                        c,
                        h,
                        u,
                        d,
                        p = e && e.split("/"),
                        f = y.map,
                        g = (f && f["*"]) || {};
                    if (t) {
                        for (r = (t = t.split("/")).length - 1, y.nodeIdCompat && w.test(t[r]) && (t[r] = t[r].replace(w, "")), "." === t[0].charAt(0) && p && (t = p.slice(0, p.length - 1).concat(t)), h = 0; h < t.length; h++)
                            if ("." === (d = t[h])) t.splice(h, 1), --h;
                            else if (".." === d) {
                                if (0 === h || (1 === h && ".." === t[2]) || ".." === t[h - 1]) continue;
                                0 < h && (t.splice(h - 1, 2), (h -= 2));
                            }
                        t = t.join("/");
                    }
                    if ((p || g) && f) {
                        for (h = (i = t.split("/")).length; 0 < h; --h) {
                            if (((n = i.slice(0, h).join("/")), p))
                                for (u = p.length; 0 < u; --u)
                                    if ((s = (s = f[p.slice(0, u).join("/")]) && s[n])) {
                                        (o = s), (a = h);
                                        break;
                                    }
                            if (o) break;
                            !l && g && g[n] && ((l = g[n]), (c = h));
                        }
                        !o && l && ((o = l), (a = c)), o && (i.splice(0, a, o), (t = i.join("/")));
                    }
                    return t;
                }
                function x(e, i) {
                    return function () {
                        var t = a.call(arguments, 0);
                        return "string" !==    typeof t[0] && 1 === t.length && t.push(null), r.apply(p, t.concat([e, i]));
                    };
                }
                function C(e) {
                    return function (t) {
                        m[e] = t;
                    };
                }
                function D(t) {
                    if (_(v, t)) {
                        var e = v[t];
                        delete v[t], (b[t] = !0), o.apply(p, e);
                    }
                    if (!_(m, t) && !_(b, t)) throw new Error("No " + t);
                    return m[t];
                }
                function h(t) {
                    var e,
                        i = t ? t.indexOf("!") : -1;
                    return -1 < i && ((e = t.substring(0, i)), (t = t.substring(i + 1, t.length))), [e, t];
                }
                function k(t) {
                    return t ? h(t) : [];
                }
                return (
                    (t && t.requirejs) ||
                        (t ? (i = t) : (t = {}),
                        (m = {}),
                        (v = {}),
                        (y = {}),
                        (b = {}),
                        (s = Object.prototype.hasOwnProperty),
                        (a = [].slice),
                        (w = /\.js$/),
                        (f = function (t, e) {
                            var i,
                                n,
                                s = h(t),
                                o = s[0],
                                r = e[1];
                            return (
                                (t = s[1]),
                                o && (i = D((o = c(o, r)))),
                                o
                                    ? (t =
                                          i && i.normalize
                                              ? i.normalize(
                                                    t,
                                                    ((n = r),
                                                    function (t) {
                                                        return c(t, n);
                                                    })
                                                )
                                              : c(t, r))
                                    : ((o = (s = h((t = c(t, r))))[0]), (t = s[1]), o && (i = D(o))),
                                { f: o ? o + "!" + t : t, n: t, pr: o, p: i }
                            );
                        }),
                        (g = {
                            require: function (t) {
                                return x(t);
                            },
                            exports: function (t) {
                                var e = m[t];
                                return void 0 !== e ? e : (m[t] = {});
                            },
                            module: function (t) {
                                return {
                                    id: t,
                                    uri: "",
                                    exports: m[t],
                                    config:
                                        ((e = t),
                                        function () {
                                            return (y && y.config && y.config[e]) || {};
                                        }),
                                };
                                var e;
                            },
                        }),
                        (o = function (t, e, i, n) {
                            var s,
                                o,
                                r,
                                a,
                                l,
                                c,
                                h,
                                u = [],
                                d = typeof i;
                            if (((c = k((n = n || t))), "undefined" === d || "function" === d)) {
                                for (e = !e.length && i.length ? ["require", "exports", "module"] : e, l = 0; l < e.length; l += 1)
                                    if ("require" === (o = (a = f(e[l], c)).f)) u[l] = g.require(t);
                                    else if ("exports" === o) (u[l] = g.exports(t)), (h = !0);
                                    else if ("module" === o) s = u[l] = g.module(t);
                                    else if (_(m, o) || _(v, o) || _(b, o)) u[l] = D(o);
                                    else {
                                        if (!a.p) throw new Error(t + " missing " + o);
                                        a.p.load(a.n, x(n, !0), C(o), {}), (u[l] = m[o]);
                                    }
                                (r = i ? i.apply(m[t], u) : void 0), t && (s && s.exports !== p && s.exports !== m[t] ? (m[t] = s.exports) : (r === p && h) || (m[t] = r));
                            } else t && (m[t] = i);
                        }),
                        (e = i = r = function (t, e, i, n, s) {
                            if ("string" === typeof t) return g[t] ? g[t](e) : D(f(t, k(e)).f);
                            if (!t.splice) {
                                if (((y = t).deps && r(y.deps, y.callback), !e)) return;
                                e.splice ? ((t = e), (e = i), (i = null)) : (t = p);
                            }
                            return (
                                (e = e || function () {}),
                                "function" === typeof i && ((i = n), (n = s)),
                                n
                                    ? o(p, t, e, i)
                                    : setTimeout(function () {
                                          o(p, t, e, i);
                                      }, 4),
                                r
                            );
                        }),
                        (r.config = function (t) {
                            return r(t);
                        }),
                        (e._defined = m),
                        ((n = function (t, e, i) {
                            if ("string" !==    typeof t) throw new Error("See almond README: incorrect module build, no module name");
                            e.splice || ((i = e), (e = [])), _(m, t) || _(v, t) || (v[t] = [t, e, i]);
                        }).amd = { jQuery: !0 }),
                        (t.requirejs = e),
                        (t.require = i),
                        (t.define = n)),
                    t.define("almond", function () {}),
                    t.define("jquery", [], function () {
                        var t = u || $;
                        return (
                            null === t && console && console.error && console.error("Select2: An instance of jQuery or a jQuery-compatible library was not found. Make sure that you are including jQuery before Select2 on your web page."), t
                        );
                    }),
                    t.define("select2/utils", ["jquery"], function (o) {
                        var s = {};
                        function h(t) {
                            var e = t.prototype,
                                i = [];
                            for (var n in e) {
                                "function" === typeof e[n] && "constructor" !== n && i.push(n);
                            }
                            return i;
                        }
                        (s.Extend = function (t, e) {
                            var i = {}.hasOwnProperty;
                            function n() {
                                this.constructor = t;
                            }
                            for (var s in e) i.call(e, s) && (t[s] = e[s]);
                            return (n.prototype = e.prototype), (t.prototype = new n()), (t.__super__ = e.prototype), t;
                        }),
                            (s.Decorate = function (n, s) {
                                var t = h(s),
                                    e = h(n);
                                function o() {
                                    var t = Array.prototype.unshift,
                                        e = s.prototype.constructor.length,
                                        i = n.prototype.constructor;
                                    0 < e && (t.call(arguments, n.prototype.constructor), (i = s.prototype.constructor)), i.apply(this, arguments);
                                }
                                (s.displayName = n.displayName),
                                    (o.prototype = new (function () {
                                        this.constructor = o;
                                    })());
                                for (var i = 0; i < e.length; i++) {
                                    var r = e[i];
                                    o.prototype[r] = n.prototype[r];
                                }
                                function a(t) {
                                    var e = function () {};
                                    t in o.prototype && (e = o.prototype[t]);
                                    var i = s.prototype[t];
                                    return function () {
                                        return Array.prototype.unshift.call(arguments, e), i.apply(this, arguments);
                                    };
                                }
                                for (var l = 0; l < t.length; l++) {
                                    var c = t[l];
                                    o.prototype[c] = a(c);
                                }
                                return o;
                            });
                        function t() {
                            this.listeners = {};
                        }
                        (t.prototype.on = function (t, e) {
                            (this.listeners = this.listeners || {}), t in this.listeners ? this.listeners[t].push(e) : (this.listeners[t] = [e]);
                        }),
                            (t.prototype.trigger = function (t) {
                                var e = Array.prototype.slice,
                                    i = e.call(arguments, 1);
                                (this.listeners = this.listeners || {}),
                                    null === i && (i = []),
                                    0 === i.length && i.push({}),
                                    (i[0]._type = t) in this.listeners && this.invoke(this.listeners[t], e.call(arguments, 1)),
                                    "*" in this.listeners && this.invoke(this.listeners["*"], arguments);
                            }),
                            (t.prototype.invoke = function (t, e) {
                                for (var i = 0, n = t.length; i < n; i++) t[i].apply(this, e);
                            }),
                            (s.Observable = t),
                            (s.generateChars = function (t) {
                                for (var e = "", i = 0; i < t; i++) {
                                    e += Math.floor(36 * Math.random()).toString(36);
                                }
                                return e;
                            }),
                            (s.bind = function (t, e) {
                                return function () {
                                    t.apply(e, arguments);
                                };
                            }),
                            (s._convertData = function (t) {
                                for (var e in t) {
                                    var i = e.split("-"),
                                        n = t;
                                    if (1 !== i.length) {
                                        for (var s = 0; s < i.length; s++) {
                                            var o = i[s];
                                            (o = o.substring(0, 1).toLowerCase() + o.substring(1)) in n || (n[o] = {}), s === i.length - 1 && (n[o] = t[e]), (n = n[o]);
                                        }
                                        delete t[e];
                                    }
                                }
                                return t;
                            }),
                            (s.hasScroll = function (t, e) {
                                var i = o(e),
                                    n = e.style.overflowX,
                                    s = e.style.overflowY;
                                return (n !== s || ("hidden" !== s && "visible" !== s)) && ("scroll" === n || "scroll" === s || i.innerHeight() < e.scrollHeight || i.innerWidth() < e.scrollWidth);
                            }),
                            (s.escapeMarkup = function (t) {
                                var e = { "\\": "&#92;", "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;", "/": "&#47;" };
                                return "string" !==    typeof t
                                    ? t
                                    : String(t).replace(/[&<>"'\/\\]/g, function (t) {
                                          return e[t];
                                      });
                            }),
                            (s.appendMany = function (t, e) {
                                if ("1.7" === o.fn.jquery.substr(0, 3)) {
                                    var i = o();
                                    o.map(e, function (t) {
                                        i = i.add(t);
                                    }),
                                        (e = i);
                                }
                                t.append(e);
                            }),
                            (s.__cache = {});
                        var i = 0;
                        return (
                            (s.GetUniqueElementId = function (t) {
                                var e = t.getAttribute("data-select2-id");
                                return null === e && (t.id ? ((e = t.id), t.setAttribute("data-select2-id", e)) : (t.setAttribute("data-select2-id", ++i), (e = i.toString()))), e;
                            }),
                            (s.StoreData = function (t, e, i) {
                                var n = s.GetUniqueElementId(t);
                                s.__cache[n] || (s.__cache[n] = {}), (s.__cache[n][e] = i);
                            }),
                            (s.GetData = function (t, e) {
                                var i = s.GetUniqueElementId(t);
                                return e ? (s.__cache[i] && null !==    s.__cache[i][e] ? s.__cache[i][e] : o(t).data(e)) : s.__cache[i];
                            }),
                            (s.RemoveData = function (t) {
                                var e = s.GetUniqueElementId(t);
                                null !==    s.__cache[e] && delete s.__cache[e], t.removeAttribute("data-select2-id");
                            }),
                            s
                        );
                    }),
                    t.define("select2/results", ["jquery", "./utils"], function (p, f) {
                        function n(t, e, i) {
                            (this.$element = t), (this.data = i), (this.options = e), n.__super__.constructor.call(this);
                        }
                        return (
                            f.Extend(n, f.Observable),
                            (n.prototype.render = function () {
                                var t = p('<ul class="select2-results__options" role="listbox"></ul>');
                                return this.options.get("multiple") && t.attr("aria-multiselectable", "true"), (this.$results = t);
                            }),
                            (n.prototype.clear = function () {
                                this.$results.empty();
                            }),
                            (n.prototype.displayMessage = function (t) {
                                var e = this.options.get("escapeMarkup");
                                this.clear(), this.hideLoading();
                                var i = p('<li role="alert" aria-live="assertive" class="select2-results__option"></li>'),
                                    n = this.options.get("translations").get(t.message);
                                i.append(e(n(t.args))), (i[0].className += " select2-results__message"), this.$results.append(i);
                            }),
                            (n.prototype.hideMessages = function () {
                                this.$results.find(".select2-results__message").remove();
                            }),
                            (n.prototype.append = function (t) {
                                this.hideLoading();
                                var e = [];
                                if (null !==    t.results && 0 !== t.results.length) {
                                    t.results = this.sort(t.results);
                                    for (var i = 0; i < t.results.length; i++) {
                                        var n = t.results[i],
                                            s = this.option(n);
                                        e.push(s);
                                    }
                                    this.$results.append(e);
                                } else 0 === this.$results.children().length && this.trigger("results:message", { message: "noResults" });
                            }),
                            (n.prototype.position = function (t, e) {
                                e.find(".select2-results").append(t);
                            }),
                            (n.prototype.sort = function (t) {
                                return this.options.get("sorter")(t);
                            }),
                            (n.prototype.highlightFirstItem = function () {
                                var t = this.$results.find(".select2-results__option[aria-selected]"),
                                    e = t.filter("[aria-selected=true]");
                                0 < e.length ? e.first().trigger("mouseenter") : t.first().trigger("mouseenter"), this.ensureHighlightVisible();
                            }),
                            (n.prototype.setClasses = function () {
                                var e = this;
                                this.data.current(function (t) {
                                    var n = p.map(t, function (t) {
                                        return t.id.toString();
                                    });
                                    e.$results.find(".select2-results__option[aria-selected]").each(function () {
                                        var t = p(this),
                                            e = f.GetData(this, "data"),
                                            i = "" + e.id;
                                        (null !==    e.element && e.element.selected) || (null === e.element && -1 < p.inArray(i, n)) ? t.attr("aria-selected", "true") : t.attr("aria-selected", "false");
                                    });
                                });
                            }),
                            (n.prototype.showLoading = function (t) {
                                this.hideLoading();
                                var e = { disabled: !0, loading: !0, text: this.options.get("translations").get("searching")(t) },
                                    i = this.option(e);
                                (i.className += " loading-results"), this.$results.prepend(i);
                            }),
                            (n.prototype.hideLoading = function () {
                                this.$results.find(".loading-results").remove();
                            }),
                            (n.prototype.option = function (t) {
                                var e = document.createElement("li");
                                e.className = "select2-results__option";
                                var i = { role: "option", "aria-selected": "false" },
                                    n = window.Element.prototype.matches || window.Element.prototype.msMatchesSelector || window.Element.prototype.webkitMatchesSelector;
                                for (var s in (((null !==    t.element && n.call(t.element, ":disabled")) || (null === t.element && t.disabled)) && (delete i["aria-selected"], (i["aria-disabled"] = "true")),
                                null === t.id && delete i["aria-selected"],
                                null !==    t._resultId && (e.id = t._resultId),
                                t.title && (e.title = t.title),
                                t.children && ((i.role = "group"), (i["aria-label"] = t.text), delete i["aria-selected"]),
                                i)) {
                                    var o = i[s];
                                    e.setAttribute(s, o);
                                }
                                if (t.children) {
                                    var r = p(e),
                                        a = document.createElement("strong");
                                    a.className = "select2-results__group";
                                    p(a);
                                    this.template(t, a);
                                    for (var l = [], c = 0; c < t.children.length; c++) {
                                        var h = t.children[c],
                                            u = this.option(h);
                                        l.push(u);
                                    }
                                    var d = p("<ul></ul>", { class: "select2-results__options select2-results__options--nested" });
                                    d.append(l), r.append(a), r.append(d);
                                } else this.template(t, e);
                                return f.StoreData(e, "data", t), e;
                            }),
                            (n.prototype.bind = function (e, t) {
                                var l = this,
                                    i = e.id + "-results";
                                this.$results.attr("id", i),
                                    e.on("results:all", function (t) {
                                        l.clear(), l.append(t.data), e.isOpen() && (l.setClasses(), l.highlightFirstItem());
                                    }),
                                    e.on("results:append", function (t) {
                                        l.append(t.data), e.isOpen() && l.setClasses();
                                    }),
                                    e.on("query", function (t) {
                                        l.hideMessages(), l.showLoading(t);
                                    }),
                                    e.on("select", function () {
                                        e.isOpen() && (l.setClasses(), l.options.get("scrollAfterSelect") && l.highlightFirstItem());
                                    }),
                                    e.on("unselect", function () {
                                        e.isOpen() && (l.setClasses(), l.options.get("scrollAfterSelect") && l.highlightFirstItem());
                                    }),
                                    e.on("open", function () {
                                        l.$results.attr("aria-expanded", "true"), l.$results.attr("aria-hidden", "false"), l.setClasses(), l.ensureHighlightVisible();
                                    }),
                                    e.on("close", function () {
                                        l.$results.attr("aria-expanded", "false"), l.$results.attr("aria-hidden", "true"), l.$results.removeAttr("aria-activedescendant");
                                    }),
                                    e.on("results:toggle", function () {
                                        var t = l.getHighlightedResults();
                                        0 !== t.length && t.trigger("mouseup");
                                    }),
                                    e.on("results:select", function () {
                                        var t = l.getHighlightedResults();
                                        if (0 !== t.length) {
                                            var e = f.GetData(t[0], "data");
                                            "true" === t.attr("aria-selected") ? l.trigger("close", {}) : l.trigger("select", { data: e });
                                        }
                                    }),
                                    e.on("results:previous", function () {
                                        var t = l.getHighlightedResults(),
                                            e = l.$results.find("[aria-selected]"),
                                            i = e.index(t);
                                        if (!(i <= 0)) {
                                            var n = i - 1;
                                            0 === t.length && (n = 0);
                                            var s = e.eq(n);
                                            s.trigger("mouseenter");
                                            var o = l.$results.offset().top,
                                                r = s.offset().top,
                                                a = l.$results.scrollTop() + (r - o);
                                            0 === n ? l.$results.scrollTop(0) : r - o < 0 && l.$results.scrollTop(a);
                                        }
                                    }),
                                    e.on("results:next", function () {
                                        var t = l.getHighlightedResults(),
                                            e = l.$results.find("[aria-selected]"),
                                            i = e.index(t) + 1;
                                        if (!(i >= e.length)) {
                                            var n = e.eq(i);
                                            n.trigger("mouseenter");
                                            var s = l.$results.offset().top + l.$results.outerHeight(!1),
                                                o = n.offset().top + n.outerHeight(!1),
                                                r = l.$results.scrollTop() + o - s;
                                            0 === i ? l.$results.scrollTop(0) : s < o && l.$results.scrollTop(r);
                                        }
                                    }),
                                    e.on("results:focus", function (t) {
                                        t.element.addClass("select2-results__option--highlighted");
                                    }),
                                    e.on("results:message", function (t) {
                                        l.displayMessage(t);
                                    }),
                                    p.fn.mousewheel &&
                                        this.$results.on("mousewheel", function (t) {
                                            var e = l.$results.scrollTop(),
                                                i = l.$results.get(0).scrollHeight - e + t.deltaY,
                                                n = 0 < t.deltaY && e - t.deltaY <= 0,
                                                s = t.deltaY < 0 && i <= l.$results.height();
                                            n ? (l.$results.scrollTop(0), t.preventDefault(), t.stopPropagation()) : s && (l.$results.scrollTop(l.$results.get(0).scrollHeight - l.$results.height()), t.preventDefault(), t.stopPropagation());
                                        }),
                                    this.$results.on("mouseup", ".select2-results__option[aria-selected]", function (t) {
                                        var e = p(this),
                                            i = f.GetData(this, "data");
                                        "true" !== e.attr("aria-selected") ? l.trigger("select", { originalEvent: t, data: i }) : l.options.get("multiple") ? l.trigger("unselect", { originalEvent: t, data: i }) : l.trigger("close", {});
                                    }),
                                    this.$results.on("mouseenter", ".select2-results__option[aria-selected]", function (t) {
                                        var e = f.GetData(this, "data");
                                        l.getHighlightedResults().removeClass("select2-results__option--highlighted"), l.trigger("results:focus", { data: e, element: p(this) });
                                    });
                            }),
                            (n.prototype.getHighlightedResults = function () {
                                return this.$results.find(".select2-results__option--highlighted");
                            }),
                            (n.prototype.destroy = function () {
                                this.$results.remove();
                            }),
                            (n.prototype.ensureHighlightVisible = function () {
                                var t = this.getHighlightedResults();
                                if (0 !== t.length) {
                                    var e = this.$results.find("[aria-selected]").index(t),
                                        i = this.$results.offset().top,
                                        n = t.offset().top,
                                        s = this.$results.scrollTop() + (n - i),
                                        o = n - i;
                                    (s -= 2 * t.outerHeight(!1)), e <= 2 ? this.$results.scrollTop(0) : (o > this.$results.outerHeight() || o < 0) && this.$results.scrollTop(s);
                                }
                            }),
                            (n.prototype.template = function (t, e) {
                                var i = this.options.get("templateResult"),
                                    n = this.options.get("escapeMarkup"),
                                    s = i(t, e);
                                null === s ? (e.style.display = "none") : "string" === typeof s ? (e.innerHTML = n(s)) : p(e).append(s);
                            }),
                            n
                        );
                    }),
                    t.define("select2/keys", [], function () {
                        return { BACKSPACE: 8, TAB: 9, ENTER: 13, SHIFT: 16, CTRL: 17, ALT: 18, ESC: 27, SPACE: 32, PAGE_UP: 33, PAGE_DOWN: 34, END: 35, HOME: 36, LEFT: 37, UP: 38, RIGHT: 39, DOWN: 40, DELETE: 46 };
                    }),
                    t.define("select2/selection/base", ["jquery", "../utils", "../keys"], function (i, n, s) {
                        function o(t, e) {
                            (this.$element = t), (this.options = e), o.__super__.constructor.call(this);
                        }
                        return (
                            n.Extend(o, n.Observable),
                            (o.prototype.render = function () {
                                var t = i('<span class="select2-selection" role="combobox"  aria-haspopup="true" aria-expanded="false"></span>');
                                return (
                                    (this._tabindex = 0),
                                    null !==    n.GetData(this.$element[0], "old-tabindex")
                                        ? (this._tabindex = n.GetData(this.$element[0], "old-tabindex"))
                                        : null !==    this.$element.attr("tabindex") && (this._tabindex = this.$element.attr("tabindex")),
                                    t.attr("title", this.$element.attr("title")),
                                    t.attr("tabindex", this._tabindex),
                                    t.attr("aria-disabled", "false"),
                                    (this.$selection = t)
                                );
                            }),
                            (o.prototype.bind = function (t, e) {
                                var i = this,
                                    n = t.id + "-results";
                                (this.container = t),
                                    this.$selection.on("focus", function (t) {
                                        i.trigger("focus", t);
                                    }),
                                    this.$selection.on("blur", function (t) {
                                        i._handleBlur(t);
                                    }),
                                    this.$selection.on("keydown", function (t) {
                                        i.trigger("keypress", t), t.which === s.SPACE && t.preventDefault();
                                    }),
                                    t.on("results:focus", function (t) {
                                        i.$selection.attr("aria-activedescendant", t.data._resultId);
                                    }),
                                    t.on("selection:update", function (t) {
                                        i.update(t.data);
                                    }),
                                    t.on("open", function () {
                                        i.$selection.attr("aria-expanded", "true"), i.$selection.attr("aria-owns", n), i._attachCloseHandler(t);
                                    }),
                                    t.on("close", function () {
                                        i.$selection.attr("aria-expanded", "false"), i.$selection.removeAttr("aria-activedescendant"), i.$selection.removeAttr("aria-owns"), i.$selection.trigger("focus"), i._detachCloseHandler(t);
                                    }),
                                    t.on("enable", function () {
                                        i.$selection.attr("tabindex", i._tabindex), i.$selection.attr("aria-disabled", "false");
                                    }),
                                    t.on("disable", function () {
                                        i.$selection.attr("tabindex", "-1"), i.$selection.attr("aria-disabled", "true");
                                    });
                            }),
                            (o.prototype._handleBlur = function (t) {
                                var e = this;
                                window.setTimeout(function () {
                                    document.activeElement === e.$selection[0] || i.contains(e.$selection[0], document.activeElement) || e.trigger("blur", t);
                                }, 1);
                            }),
                            (o.prototype._attachCloseHandler = function (t) {
                                i(document.body).on("mousedown.select2." + t.id, function (t) {
                                    var e = i(t.target).closest(".select2");
                                    i(".select2.select2-container--open").each(function () {
                                        this !==    e[0] && n.GetData(this, "element").select2("close");
                                    });
                                });
                            }),
                            (o.prototype._detachCloseHandler = function (t) {
                                i(document.body).off("mousedown.select2." + t.id);
                            }),
                            (o.prototype.position = function (t, e) {
                                e.find(".selection").append(t);
                            }),
                            (o.prototype.destroy = function () {
                                this._detachCloseHandler(this.container);
                            }),
                            (o.prototype.update = function (t) {
                                throw new Error("The `update` method must be defined in child classes.");
                            }),
                            (o.prototype.isEnabled = function () {
                                return !this.isDisabled();
                            }),
                            (o.prototype.isDisabled = function () {
                                return this.options.get("disabled");
                            }),
                            o
                        );
                    }),
                    t.define("select2/selection/single", ["jquery", "./base", "../utils", "../keys"], function (t, e, i, n) {
                        function s() {
                            s.__super__.constructor.apply(this, arguments);
                        }
                        return (
                            i.Extend(s, e),
                            (s.prototype.render = function () {
                                var t = s.__super__.render.call(this);
                                return t.addClass("select2-selection--single"), t.html('<span class="select2-selection__rendered"></span><span class="select2-selection__arrow" role="presentation"><b role="presentation"></b></span>'), t;
                            }),
                            (s.prototype.bind = function (e, t) {
                                var i = this;
                                s.__super__.bind.apply(this, arguments);
                                var n = e.id + "-container";
                                this.$selection.find(".select2-selection__rendered").attr("id", n).attr("role", "textbox").attr("aria-readonly", "true"),
                                    this.$selection.attr("aria-labelledby", n),
                                    this.$selection.on("mousedown", function (t) {
                                        1 === t.which && i.trigger("toggle", { originalEvent: t });
                                    }),
                                    this.$selection.on("focus", function (t) {}),
                                    this.$selection.on("blur", function (t) {}),
                                    e.on("focus", function (t) {
                                        e.isOpen() || i.$selection.trigger("focus");
                                    });
                            }),
                            (s.prototype.clear = function () {
                                var t = this.$selection.find(".select2-selection__rendered");
                                t.empty(), t.removeAttr("title");
                            }),
                            (s.prototype.display = function (t, e) {
                                var i = this.options.get("templateSelection");
                                return this.options.get("escapeMarkup")(i(t, e));
                            }),
                            (s.prototype.selectionContainer = function () {
                                return t("<span></span>");
                            }),
                            (s.prototype.update = function (t) {
                                if (0 !== t.length) {
                                    var e = t[0],
                                        i = this.$selection.find(".select2-selection__rendered"),
                                        n = this.display(e, i);
                                    i.empty().append(n);
                                    var s = e.title || e.text;
                                    s ? i.attr("title", s) : i.removeAttr("title");
                                } else this.clear();
                            }),
                            s
                        );
                    }),
                    t.define("select2/selection/multiple", ["jquery", "./base", "../utils"], function (s, t, l) {
                        function i(t, e) {
                            i.__super__.constructor.apply(this, arguments);
                        }
                        return (
                            l.Extend(i, t),
                            (i.prototype.render = function () {
                                var t = i.__super__.render.call(this);
                                return t.addClass("select2-selection--multiple"), t.html('<ul class="select2-selection__rendered"></ul>'), t;
                            }),
                            (i.prototype.bind = function (t, e) {
                                var n = this;
                                i.__super__.bind.apply(this, arguments),
                                    this.$selection.on("click", function (t) {
                                        n.trigger("toggle", { originalEvent: t });
                                    }),
                                    this.$selection.on("click", ".select2-selection__choice__remove", function (t) {
                                        if (!n.isDisabled()) {
                                            var e = s(this).parent(),
                                                i = l.GetData(e[0], "data");
                                            n.trigger("unselect", { originalEvent: t, data: i });
                                        }
                                    });
                            }),
                            (i.prototype.clear = function () {
                                var t = this.$selection.find(".select2-selection__rendered");
                                t.empty(), t.removeAttr("title");
                            }),
                            (i.prototype.display = function (t, e) {
                                var i = this.options.get("templateSelection");
                                return this.options.get("escapeMarkup")(i(t, e));
                            }),
                            (i.prototype.selectionContainer = function () {
                                return s('<li class="select2-selection__choice"><span class="select2-selection__choice__remove" role="presentation">&times;</span></li>');
                            }),
                            (i.prototype.update = function (t) {
                                if ((this.clear(), 0 !== t.length)) {
                                    for (var e = [], i = 0; i < t.length; i++) {
                                        var n = t[i],
                                            s = this.selectionContainer(),
                                            o = this.display(n, s);
                                        s.append(o);
                                        var r = n.title || n.text;
                                        r && s.attr("title", r), l.StoreData(s[0], "data", n), e.push(s);
                                    }
                                    var a = this.$selection.find(".select2-selection__rendered");
                                    l.appendMany(a, e);
                                }
                            }),
                            i
                        );
                    }),
                    t.define("select2/selection/placeholder", ["../utils"], function (t) {
                        function e(t, e, i) {
                            (this.placeholder = this.normalizePlaceholder(i.get("placeholder"))), t.call(this, e, i);
                        }
                        return (
                            (e.prototype.normalizePlaceholder = function (t, e) {
                                return "string" === typeof e && (e = { id: "", text: e }), e;
                            }),
                            (e.prototype.createPlaceholder = function (t, e) {
                                var i = this.selectionContainer();
                                return i.html(this.display(e)), i.addClass("select2-selection__placeholder").removeClass("select2-selection__choice"), i;
                            }),
                            (e.prototype.update = function (t, e) {
                                var i = 1 === e.length && e[0].id !==    this.placeholder.id;
                                if (1 < e.length || i) return t.call(this, e);
                                this.clear();
                                var n = this.createPlaceholder(this.placeholder);
                                this.$selection.find(".select2-selection__rendered").append(n);
                            }),
                            e
                        );
                    }),
                    t.define("select2/selection/allowClear", ["jquery", "../keys", "../utils"], function (s, n, a) {
                        function t() {}
                        return (
                            (t.prototype.bind = function (t, e, i) {
                                var n = this;
                                t.call(this, e, i),
                                    null === this.placeholder && this.options.get("debug") && window.console && console.error && console.error("Select2: The `allowClear` option should be used in combination with the `placeholder` option."),
                                    this.$selection.on("mousedown", ".select2-selection__clear", function (t) {
                                        n._handleClear(t);
                                    }),
                                    e.on("keypress", function (t) {
                                        n._handleKeyboardClear(t, e);
                                    });
                            }),
                            (t.prototype._handleClear = function (t, e) {
                                if (!this.isDisabled()) {
                                    var i = this.$selection.find(".select2-selection__clear");
                                    if (0 !== i.length) {
                                        e.stopPropagation();
                                        var n = a.GetData(i[0], "data"),
                                            s = this.$element.val();
                                        this.$element.val(this.placeholder.id);
                                        var o = { data: n };
                                        if ((this.trigger("clear", o), o.prevented)) this.$element.val(s);
                                        else {
                                            for (var r = 0; r < n.length; r++) if (((o = { data: n[r] }), this.trigger("unselect", o), o.prevented)) return void this.$element.val(s);
                                            this.$element.trigger("input").trigger("change"), this.trigger("toggle", {});
                                        }
                                    }
                                }
                            }),
                            (t.prototype._handleKeyboardClear = function (t, e, i) {
                                i.isOpen() || (e.which !==    n.DELETE && e.which !==    n.BACKSPACE) || this._handleClear(e);
                            }),
                            (t.prototype.update = function (t, e) {
                                if ((t.call(this, e), !(0 < this.$selection.find(".select2-selection__placeholder").length || 0 === e.length))) {
                                    var i = this.options.get("translations").get("removeAllItems"),
                                        n = s('<span class="select2-selection__clear" title="' + i() + '">&times;</span>');
                                    a.StoreData(n[0], "data", e), this.$selection.find(".select2-selection__rendered").prepend(n);
                                }
                            }),
                            t
                        );
                    }),
                    t.define("select2/selection/search", ["jquery", "../utils", "../keys"], function (n, a, l) {
                        function t(t, e, i) {
                            t.call(this, e, i);
                        }
                        return (
                            (t.prototype.render = function (t) {
                                var e = n(
                                    '<li class="select2-search select2-search--inline"><input class="select2-search__field" type="search" tabindex="-1" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" role="searchbox" aria-autocomplete="list" /></li>'
                                );
                                (this.$searchContainer = e), (this.$search = e.find("input"));
                                var i = t.call(this);
                                return this._transferTabIndex(), i;
                            }),
                            (t.prototype.bind = function (t, e, i) {
                                var n = this,
                                    s = e.id + "-results";
                                t.call(this, e, i),
                                    e.on("open", function () {
                                        n.$search.attr("aria-controls", s), n.$search.trigger("focus");
                                    }),
                                    e.on("close", function () {
                                        n.$search.val(""), n.$search.removeAttr("aria-controls"), n.$search.removeAttr("aria-activedescendant"), n.$search.trigger("focus");
                                    }),
                                    e.on("enable", function () {
                                        n.$search.prop("disabled", !1), n._transferTabIndex();
                                    }),
                                    e.on("disable", function () {
                                        n.$search.prop("disabled", !0);
                                    }),
                                    e.on("focus", function (t) {
                                        n.$search.trigger("focus");
                                    }),
                                    e.on("results:focus", function (t) {
                                        t.data._resultId ? n.$search.attr("aria-activedescendant", t.data._resultId) : n.$search.removeAttr("aria-activedescendant");
                                    }),
                                    this.$selection.on("focusin", ".select2-search--inline", function (t) {
                                        n.trigger("focus", t);
                                    }),
                                    this.$selection.on("focusout", ".select2-search--inline", function (t) {
                                        n._handleBlur(t);
                                    }),
                                    this.$selection.on("keydown", ".select2-search--inline", function (t) {
                                        if ((t.stopPropagation(), n.trigger("keypress", t), (n._keyUpPrevented = t.isDefaultPrevented()), t.which === l.BACKSPACE && "" === n.$search.val())) {
                                            var e = n.$searchContainer.prev(".select2-selection__choice");
                                            if (0 < e.length) {
                                                var i = a.GetData(e[0], "data");
                                                n.searchRemoveChoice(i), t.preventDefault();
                                            }
                                        }
                                    }),
                                    this.$selection.on("click", ".select2-search--inline", function (t) {
                                        n.$search.val() && t.stopPropagation();
                                    });
                                var o = document.documentMode,
                                    r = o && o <= 11;
                                this.$selection.on("input.searchcheck", ".select2-search--inline", function (t) {
                                    r ? n.$selection.off("input.search input.searchcheck") : n.$selection.off("keyup.search");
                                }),
                                    this.$selection.on("keyup.search input.search", ".select2-search--inline", function (t) {
                                        if (r && "input" === t.type) n.$selection.off("input.search input.searchcheck");
                                        else {
                                            var e = t.which;
                                            e !==    l.SHIFT && e !==    l.CTRL && e !==    l.ALT && e !==    l.TAB && n.handleSearch(t);
                                        }
                                    });
                            }),
                            (t.prototype._transferTabIndex = function (t) {
                                this.$search.attr("tabindex", this.$selection.attr("tabindex")), this.$selection.attr("tabindex", "-1");
                            }),
                            (t.prototype.createPlaceholder = function (t, e) {
                                this.$search.attr("placeholder", e.text);
                            }),
                            (t.prototype.update = function (t, e) {
                                var i = this.$search[0] === document.activeElement;
                                this.$search.attr("placeholder", ""), t.call(this, e), this.$selection.find(".select2-selection__rendered").append(this.$searchContainer), this.resizeSearch(), i && this.$search.trigger("focus");
                            }),
                            (t.prototype.handleSearch = function () {
                                if ((this.resizeSearch(), !this._keyUpPrevented)) {
                                    var t = this.$search.val();
                                    this.trigger("query", { term: t });
                                }
                                this._keyUpPrevented = !1;
                            }),
                            (t.prototype.searchRemoveChoice = function (t, e) {
                                this.trigger("unselect", { data: e }), this.$search.val(e.text), this.handleSearch();
                            }),
                            (t.prototype.resizeSearch = function () {
                                this.$search.css("width", "25px");
                                var t = "";
                                "" !== this.$search.attr("placeholder") ? (t = this.$selection.find(".select2-selection__rendered").width()) : (t = 0.75 * (this.$search.val().length + 1) + "em");
                                this.$search.css("width", t);
                            }),
                            t
                        );
                    }),
                    t.define("select2/selection/eventRelay", ["jquery"], function (r) {
                        function t() {}
                        return (
                            (t.prototype.bind = function (t, e, i) {
                                var n = this,
                                    s = ["open", "opening", "close", "closing", "select", "selecting", "unselect", "unselecting", "clear", "clearing"],
                                    o = ["opening", "closing", "selecting", "unselecting", "clearing"];
                                t.call(this, e, i),
                                    e.on("*", function (t, e) {
                                        if (-1 !== r.inArray(t, s)) {
                                            e = e || {};
                                            var i = r.Event("select2:" + t, { params: e });
                                            n.$element.trigger(i), -1 !== r.inArray(t, o) && (e.prevented = i.isDefaultPrevented());
                                        }
                                    });
                            }),
                            t
                        );
                    }),
                    t.define("select2/translation", ["jquery", "require"], function (e, i) {
                        function n(t) {
                            this.dict = t || {};
                        }
                        return (
                            (n.prototype.all = function () {
                                return this.dict;
                            }),
                            (n.prototype.get = function (t) {
                                return this.dict[t];
                            }),
                            (n.prototype.extend = function (t) {
                                this.dict = e.extend({}, t.all(), this.dict);
                            }),
                            (n._cache = {}),
                            (n.loadPath = function (t) {
                                if (!(t in n._cache)) {
                                    var e = i(t);
                                    n._cache[t] = e;
                                }
                                return new n(n._cache[t]);
                            }),
                            n
                        );
                    }),
                    t.define("select2/diacritics", [], function () {
                        return {
                            "Ⓐ": "A",
                            "Ａ": "A",
                            "À": "A",
                            "�?": "A",
                            "Â": "A",
                            "Ầ": "A",
                            "Ấ": "A",
                            Ẫ: "A",
                            "Ẩ": "A",
                            Ã: "A",
                            "Ā": "A",
                            "Ă": "A",
                            "Ằ": "A",
                            "Ắ": "A",
                            "Ẵ": "A",
                            "Ẳ": "A",
                            "Ȧ": "A",
                            "Ǡ": "A",
                            "Ä": "A",
                            Ǟ: "A",
                            "Ả": "A",
                            "Å": "A",
                            Ǻ: "A",
                            "�?": "A",
                            "Ȁ": "A",
                            "Ȃ": "A",
                            "Ạ": "A",
                            "Ậ": "A",
                            "Ặ": "A",
                            "Ḁ": "A",
                            "Ą": "A",
                            Ⱥ: "A",
                            "Ɐ": "A",
                            "Ꜳ": "AA",
                            "Æ": "AE",
                            "Ǽ": "AE",
                            "Ǣ": "AE",
                            "Ꜵ": "AO",
                            "Ꜷ": "AU",
                            "Ꜹ": "AV",
                            Ꜻ: "AV",
                            "Ꜽ": "AY",
                            "Ⓑ": "B",
                            "Ｂ": "B",
                            "Ḃ": "B",
                            "Ḅ": "B",
                            "Ḇ": "B",
                            Ƀ: "B",
                            "Ƃ": "B",
                            "�?": "B",
                            "Ⓒ": "C",
                            "Ｃ": "C",
                            "Ć": "C",
                            Ĉ: "C",
                            Ċ: "C",
                            Č: "C",
                            "Ç": "C",
                            "Ḉ": "C",
                            "Ƈ": "C",
                            "Ȼ": "C",
                            "Ꜿ": "C",
                            "Ⓓ": "D",
                            "Ｄ": "D",
                            "Ḋ": "D",
                            Ď: "D",
                            "Ḍ": "D",
                            "�?": "D",
                            "Ḓ": "D",
                            "Ḏ": "D",
                            "�?": "D",
                            "Ƌ": "D",
                            Ɗ: "D",
                            "Ɖ": "D",
                            "�?�": "D",
                            "Ǳ": "DZ",
                            "Ǆ": "DZ",
                            "ǲ": "Dz",
                            "ǅ": "Dz",
                            "Ⓔ": "E",
                            "Ｅ": "E",
                            È: "E",
                            "É": "E",
                            Ê: "E",
                            "Ề": "E",
                            "Ế": "E",
                            "Ễ": "E",
                            "Ể": "E",
                            "Ẽ": "E",
                            "Ē": "E",
                            "Ḕ": "E",
                            "Ḗ": "E",
                            "Ĕ": "E",
                            "Ė": "E",
                            "Ë": "E",
                            Ẻ: "E",
                            Ě: "E",
                            "Ȅ": "E",
                            "Ȇ": "E",
                            "Ẹ": "E",
                            "Ệ": "E",
                            "Ȩ": "E",
                            "Ḝ": "E",
                            "Ę": "E",
                            "Ḙ": "E",
                            "Ḛ": "E",
                            "�?": "E",
                            Ǝ: "E",
                            "Ⓕ": "F",
                            "Ｆ": "F",
                            "Ḟ": "F",
                            "Ƒ": "F",
                            "�?�": "F",
                            "Ⓖ": "G",
                            "Ｇ": "G",
                            "Ǵ": "G",
                            Ĝ: "G",
                            "Ḡ": "G",
                            Ğ: "G",
                            "Ġ": "G",
                            "Ǧ": "G",
                            "Ģ": "G",
                            "Ǥ": "G",
                            "Ɠ": "G",
                            "Ꞡ": "G",
                            "�?�": "G",
                            "�?�": "G",
                            "Ⓗ": "H",
                            "Ｈ": "H",
                            "Ĥ": "H",
                            "Ḣ": "H",
                            "Ḧ": "H",
                            Ȟ: "H",
                            "Ḥ": "H",
                            "Ḩ": "H",
                            "Ḫ": "H",
                            "Ħ": "H",
                            "Ⱨ": "H",
                            "Ⱶ": "H",
                            "�?": "H",
                            "Ⓘ": "I",
                            "Ｉ": "I",
                            Ì: "I",
                            "�?": "I",
                            Î: "I",
                            "Ĩ": "I",
                            Ī: "I",
                            "Ĭ": "I",
                            "İ": "I",
                            "�?": "I",
                            "Ḯ": "I",
                            "Ỉ": "I",
                            "�?": "I",
                            Ȉ: "I",
                            Ȋ: "I",
                            "Ị": "I",
                            "Į": "I",
                            "Ḭ": "I",
                            "Ɨ": "I",
                            "Ⓙ": "J",
                            "Ｊ": "J",
                            "Ĵ": "J",
                            Ɉ: "J",
                            "Ⓚ": "K",
                            "Ｋ": "K",
                            "Ḱ": "K",
                            "Ǩ": "K",
                            "Ḳ": "K",
                            "Ķ": "K",
                            "Ḵ": "K",
                            "Ƙ": "K",
                            "Ⱪ": "K",
                            "�?�": "K",
                            "�?�": "K",
                            "�?�": "K",
                            "Ꞣ": "K",
                            "�?": "L",
                            "Ｌ": "L",
                            "Ŀ": "L",
                            "Ĺ": "L",
                            "Ľ": "L",
                            "Ḷ": "L",
                            "Ḹ": "L",
                            "Ļ": "L",
                            "Ḽ": "L",
                            "Ḻ": "L",
                            "�?": "L",
                            "Ƚ": "L",
                            "Ɫ": "L",
                            "Ⱡ": "L",
                            "�?�": "L",
                            "�?�": "L",
                            "Ꞁ": "L",
                            "Ǉ": "LJ",
                            ǈ: "Lj",
                            "Ⓜ": "M",
                            "Ｍ": "M",
                            "Ḿ": "M",
                            "Ṁ": "M",
                            "Ṃ": "M",
                            "Ɱ": "M",
                            Ɯ: "M",
                            "Ⓝ": "N",
                            "Ｎ": "N",
                            "Ǹ": "N",
                            Ń: "N",
                            "Ñ": "N",
                            "Ṅ": "N",
                            "Ň": "N",
                            "Ṇ": "N",
                            "Ņ": "N",
                            "Ṋ": "N",
                            "Ṉ": "N",
                            "Ƞ": "N",
                            "�?": "N",
                            "�?": "N",
                            "Ꞥ": "N",
                            Ǌ: "NJ",
                            "ǋ": "Nj",
                            "Ⓞ": "O",
                            "Ｏ": "O",
                            "Ò": "O",
                            "Ó": "O",
                            "Ô": "O",
                            "Ồ": "O",
                            "�?": "O",
                            "Ỗ": "O",
                            "Ổ": "O",
                            "Õ": "O",
                            "Ṍ": "O",
                            "Ȭ": "O",
                            "Ṏ": "O",
                            Ō: "O",
                            "�?": "O",
                            "Ṓ": "O",
                            Ŏ: "O",
                            "Ȯ": "O",
                            "Ȱ": "O",
                            "Ö": "O",
                            Ȫ: "O",
                            "Ỏ": "O",
                            "�?": "O",
                            "Ǒ": "O",
                            Ȍ: "O",
                            Ȏ: "O",
                            "Ơ": "O",
                            "Ờ": "O",
                            "Ớ": "O",
                            "Ỡ": "O",
                            "Ở": "O",
                            "Ợ": "O",
                            "Ọ": "O",
                            "Ộ": "O",
                            Ǫ: "O",
                            "Ǭ": "O",
                            "Ø": "O",
                            "Ǿ": "O",
                            "Ɔ": "O",
                            Ɵ: "O",
                            "�?�": "O",
                            "�?�": "O",
                            "Œ": "OE",
                            "Ƣ": "OI",
                            "�?�": "OO",
                            "Ȣ": "OU",
                            "Ⓟ": "P",
                            "Ｐ": "P",
                            "Ṕ": "P",
                            "Ṗ": "P",
                            "Ƥ": "P",
                            "Ᵽ": "P",
                            "�??": "P",
                            "�?�": "P",
                            "�?�": "P",
                            "Ⓠ": "Q",
                            "Ｑ": "Q",
                            "�?�": "Q",
                            "�?�": "Q",
                            Ɋ: "Q",
                            "Ⓡ": "R",
                            "Ｒ": "R",
                            "Ŕ": "R",
                            "Ṙ": "R",
                            "Ř": "R",
                            "�?": "R",
                            "Ȓ": "R",
                            "Ṛ": "R",
                            "Ṝ": "R",
                            "Ŗ": "R",
                            "Ṟ": "R",
                            Ɍ: "R",
                            "Ɽ": "R",
                            "�?�": "R",
                            "Ꞧ": "R",
                            "Ꞃ": "R",
                            "Ⓢ": "S",
                            "Ｓ": "S",
                            ẞ: "S",
                            Ś: "S",
                            "Ṥ": "S",
                            Ŝ: "S",
                            "Ṡ": "S",
                            "Š": "S",
                            "Ṧ": "S",
                            "Ṣ": "S",
                            "Ṩ": "S",
                            "Ș": "S",
                            Ş: "S",
                            "Ȿ": "S",
                            "Ꞩ": "S",
                            "Ꞅ": "S",
                            "Ⓣ": "T",
                            "Ｔ": "T",
                            "Ṫ": "T",
                            "Ť": "T",
                            "Ṭ": "T",
                            Ț: "T",
                            "Ţ": "T",
                            "Ṱ": "T",
                            "Ṯ": "T",
                            "Ŧ": "T",
                            "Ƭ": "T",
                            "Ʈ": "T",
                            "Ⱦ": "T",
                            "Ꞇ": "T",
                            "Ꜩ": "TZ",
                            "Ⓤ": "U",
                            "Ｕ": "U",
                            "Ù": "U",
                            Ú: "U",
                            "Û": "U",
                            "Ũ": "U",
                            "Ṹ": "U",
                            Ū: "U",
                            "Ṻ": "U",
                            "Ŭ": "U",
                            Ü: "U",
                            "Ǜ": "U",
                            "Ǘ": "U",
                            "Ǖ": "U",
                            "Ǚ": "U",
                            "Ủ": "U",
                            "Ů": "U",
                            "Ű": "U",
                            "Ǔ": "U",
                            "Ȕ": "U",
                            "Ȗ": "U",
                            "Ư": "U",
                            "Ừ": "U",
                            "Ứ": "U",
                            "Ữ": "U",
                            "Ử": "U",
                            "Ự": "U",
                            "Ụ": "U",
                            "Ṳ": "U",
                            "Ų": "U",
                            "Ṷ": "U",
                            "Ṵ": "U",
                            "Ʉ": "U",
                            "Ⓥ": "V",
                            "Ｖ": "V",
                            "Ṽ": "V",
                            "Ṿ": "V",
                            "Ʋ": "V",
                            "�?�": "V",
                            "Ʌ": "V",
                            "�?�": "VY",
                            "Ⓦ": "W",
                            "Ｗ": "W",
                            "Ẁ": "W",
                            "Ẃ": "W",
                            "Ŵ": "W",
                            "Ẇ": "W",
                            "Ẅ": "W",
                            Ẉ: "W",
                            "Ⱳ": "W",
                            "�?": "X",
                            "Ｘ": "X",
                            Ẋ: "X",
                            Ẍ: "X",
                            "Ⓨ": "Y",
                            "Ｙ": "Y",
                            "Ỳ": "Y",
                            "�?": "Y",
                            "Ŷ": "Y",
                            "Ỹ": "Y",
                            "Ȳ": "Y",
                            Ẏ: "Y",
                            "Ÿ": "Y",
                            "Ỷ": "Y",
                            "Ỵ": "Y",
                            "Ƴ": "Y",
                            Ɏ: "Y",
                            "Ỿ": "Y",
                            "�?": "Z",
                            "Ｚ": "Z",
                            "Ź": "Z",
                            "�?": "Z",
                            "Ż": "Z",
                            "Ž": "Z",
                            "Ẓ": "Z",
                            "Ẕ": "Z",
                            Ƶ: "Z",
                            "Ȥ": "Z",
                            "Ɀ": "Z",
                            "Ⱬ": "Z",
                            "�?�": "Z",
                            "�?": "a",
                            "�?": "a",
                            ẚ: "a",
                            "à": "a",
                            "á": "a",
                            "â": "a",
                            "ầ": "a",
                            "ấ": "a",
                            "ẫ": "a",
                            "ẩ": "a",
                            "ã": "a",
                            "�?": "a",
                            ă: "a",
                            "ằ": "a",
                            "ắ": "a",
                            ẵ: "a",
                            "ẳ": "a",
                            "ȧ": "a",
                            "ǡ": "a",
                            "ä": "a",
                            ǟ: "a",
                            "ả": "a",
                            "å": "a",
                            "ǻ": "a",
                            ǎ: "a",
                            "�?": "a",
                            ȃ: "a",
                            "ạ": "a",
                            "ậ": "a",
                            "ặ": "a",
                            "�?": "a",
                            "ą": "a",
                            "ⱥ": "a",
                            "�?": "a",
                            "ꜳ": "aa",
                            "æ": "ae",
                            "ǽ": "ae",
                            "ǣ": "ae",
                            ꜵ: "ao",
                            "ꜷ": "au",
                            "ꜹ": "av",
                            "ꜻ": "av",
                            "ꜽ": "ay",
                            "ⓑ": "b",
                            "ｂ": "b",
                            "ḃ": "b",
                            "ḅ": "b",
                            "ḇ": "b",
                            "ƀ": "b",
                            ƃ: "b",
                            "ɓ": "b",
                            "ⓒ": "c",
                            "ｃ": "c",
                            "ć": "c",
                            "ĉ": "c",
                            "ċ": "c",
                            "�?": "c",
                            "ç": "c",
                            "ḉ": "c",
                            ƈ: "c",
                            "ȼ": "c",
                            "ꜿ": "c",
                            "ↄ": "c",
                            "ⓓ": "d",
                            "ｄ": "d",
                            "ḋ": "d",
                            "�?": "d",
                            "�?": "d",
                            "ḑ": "d",
                            "ḓ": "d",
                            "�?": "d",
                            "đ": "d",
                            ƌ: "d",
                            "ɖ": "d",
                            "ɗ": "d",
                            "�?�": "d",
                            "ǳ": "dz",
                            "ǆ": "dz",
                            "ⓔ": "e",
                            "ｅ": "e",
                            "è": "e",
                            "é": "e",
                            ê: "e",
                            "�?": "e",
                            "ế": "e",
                            "ễ": "e",
                            "ể": "e",
                            "ẽ": "e",
                            "ē": "e",
                            "ḕ": "e",
                            "ḗ": "e",
                            "ĕ": "e",
                            "ė": "e",
                            "ë": "e",
                            "ẻ": "e",
                            "ě": "e",
                            "ȅ": "e",
                            "ȇ": "e",
                            "ẹ": "e",
                            "ệ": "e",
                            "ȩ": "e",
                            "�?": "e",
                            "ę": "e",
                            "ḙ": "e",
                            "ḛ": "e",
                            "ɇ": "e",
                            "ɛ": "e",
                            "�?": "e",
                            "ⓕ": "f",
                            "ｆ": "f",
                            "ḟ": "f",
                            "ƒ": "f",
                            "�?�": "f",
                            "ⓖ": "g",
                            "ｇ": "g",
                            ǵ: "g",
                            "�?": "g",
                            "ḡ": "g",
                            ğ: "g",
                            "ġ": "g",
                            "ǧ": "g",
                            "ģ": "g",
                            "ǥ": "g",
                            "ɠ": "g",
                            "ꞡ": "g",
                            "ᵹ": "g",
                            "�?�": "g",
                            "ⓗ": "h",
                            "ｈ": "h",
                            "ĥ": "h",
                            "ḣ": "h",
                            "ḧ": "h",
                            ȟ: "h",
                            "ḥ": "h",
                            "ḩ": "h",
                            "ḫ": "h",
                            "ẖ": "h",
                            "ħ": "h",
                            "ⱨ": "h",
                            "ⱶ": "h",
                            "ɥ": "h",
                            "ƕ": "hv",
                            "ⓘ": "i",
                            "ｉ": "i",
                            "ì": "i",
                            "í": "i",
                            "î": "i",
                            "ĩ": "i",
                            "ī": "i",
                            "ĭ": "i",
                            "ï": "i",
                            "ḯ": "i",
                            "ỉ": "i",
                            "�?": "i",
                            "ȉ": "i",
                            "ȋ": "i",
                            "ị": "i",
                            "į": "i",
                            "ḭ": "i",
                            "ɨ": "i",
                            "ı": "i",
                            "ⓙ": "j",
                            "ｊ": "j",
                            ĵ: "j",
                            "ǰ": "j",
                            "ɉ": "j",
                            "ⓚ": "k",
                            "ｋ": "k",
                            "ḱ": "k",
                            "ǩ": "k",
                            "ḳ": "k",
                            "ķ": "k",
                            "ḵ": "k",
                            "ƙ": "k",
                            "ⱪ": "k",
                            "�??": "k",
                            "�?�": "k",
                            "�?�": "k",
                            "ꞣ": "k",
                            "ⓛ": "l",
                            "ｌ": "l",
                            "ŀ": "l",
                            ĺ: "l",
                            "ľ": "l",
                            "ḷ": "l",
                            "ḹ": "l",
                            "ļ": "l",
                            "ḽ": "l",
                            "ḻ": "l",
                            "ſ": "l",
                            "ł": "l",
                            ƚ: "l",
                            "ɫ": "l",
                            "ⱡ": "l",
                            "�?�": "l",
                            "�?": "l",
                            "�?�": "l",
                            "ǉ": "lj",
                            "ⓜ": "m",
                            "�?": "m",
                            "ḿ": "m",
                            "�?": "m",
                            "ṃ": "m",
                            "ɱ": "m",
                            "ɯ": "m",
                            "�?": "n",
                            "ｎ": "n",
                            "ǹ": "n",
                            "ń": "n",
                            "ñ": "n",
                            "ṅ": "n",
                            ň: "n",
                            "ṇ": "n",
                            "ņ": "n",
                            "ṋ": "n",
                            "ṉ": "n",
                            ƞ: "n",
                            "ɲ": "n",
                            "ŉ": "n",
                            "ꞑ": "n",
                            "ꞥ": "n",
                            ǌ: "nj",
                            "ⓞ": "o",
                            "�?": "o",
                            "ò": "o",
                            "ó": "o",
                            "ô": "o",
                            "ồ": "o",
                            "ố": "o",
                            "ỗ": "o",
                            "ổ": "o",
                            õ: "o",
                            "�?": "o",
                            "ȭ": "o",
                            "�?": "o",
                            "�?": "o",
                            "ṑ": "o",
                            "ṓ": "o",
                            "�?": "o",
                            "ȯ": "o",
                            "ȱ": "o",
                            "ö": "o",
                            "ȫ": "o",
                            "�?": "o",
                            "ő": "o",
                            "ǒ": "o",
                            "�?": "o",
                            "�?": "o",
                            "ơ": "o",
                            "�?": "o",
                            "ớ": "o",
                            "ỡ": "o",
                            "ở": "o",
                            "ợ": "o",
                            "�?": "o",
                            "ộ": "o",
                            "ǫ": "o",
                            "ǭ": "o",
                            "ø": "o",
                            "ǿ": "o",
                            "ɔ": "o",
                            "�?�": "o",
                            "�??": "o",
                            ɵ: "o",
                            "œ": "oe",
                            "ƣ": "oi",
                            "ȣ": "ou",
                            "�??": "oo",
                            "ⓟ": "p",
                            "�?": "p",
                            "ṕ": "p",
                            "ṗ": "p",
                            "ƥ": "p",
                            "ᵽ": "p",
                            "�?�": "p",
                            "�?�": "p",
                            "�?�": "p",
                            "ⓠ": "q",
                            "ｑ": "q",
                            "ɋ": "q",
                            "�?�": "q",
                            "�?�": "q",
                            "ⓡ": "r",
                            "ｒ": "r",
                            "ŕ": "r",
                            "ṙ": "r",
                            "ř": "r",
                            "ȑ": "r",
                            "ȓ": "r",
                            "ṛ": "r",
                            "�?": "r",
                            "ŗ": "r",
                            "ṟ": "r",
                            "�?": "r",
                            "ɽ": "r",
                            "�?�": "r",
                            "ꞧ": "r",
                            ꞃ: "r",
                            "ⓢ": "s",
                            "ｓ": "s",
                            ß: "s",
                            "ś": "s",
                            "ṥ": "s",
                            "�?": "s",
                            "ṡ": "s",
                            "š": "s",
                            "ṧ": "s",
                            "ṣ": "s",
                            "ṩ": "s",
                            "ș": "s",
                            ş: "s",
                            "ȿ": "s",
                            "ꞩ": "s",
                            "ꞅ": "s",
                            "ẛ": "s",
                            "ⓣ": "t",
                            "ｔ": "t",
                            "ṫ": "t",
                            "ẗ": "t",
                            "ť": "t",
                            "ṭ": "t",
                            "ț": "t",
                            "ţ": "t",
                            "ṱ": "t",
                            "ṯ": "t",
                            "ŧ": "t",
                            "ƭ": "t",
                            ʈ: "t",
                            "ⱦ": "t",
                            "ꞇ": "t",
                            "ꜩ": "tz",
                            "ⓤ": "u",
                            "ｕ": "u",
                            "ù": "u",
                            ú: "u",
                            "û": "u",
                            "ũ": "u",
                            "ṹ": "u",
                            "ū": "u",
                            "ṻ": "u",
                            "ŭ": "u",
                            "ü": "u",
                            ǜ: "u",
                            "ǘ": "u",
                            "ǖ": "u",
                            ǚ: "u",
                            "ủ": "u",
                            "ů": "u",
                            "ű": "u",
                            "ǔ": "u",
                            "ȕ": "u",
                            "ȗ": "u",
                            "ư": "u",
                            "ừ": "u",
                            "ứ": "u",
                            "ữ": "u",
                            "ử": "u",
                            "ự": "u",
                            "ụ": "u",
                            "ṳ": "u",
                            "ų": "u",
                            "ṷ": "u",
                            "ṵ": "u",
                            "ʉ": "u",
                            "ⓥ": "v",
                            "ｖ": "v",
                            "ṽ": "v",
                            "ṿ": "v",
                            "ʋ": "v",
                            "�?�": "v",
                            ʌ: "v",
                            "�?�": "vy",
                            "ⓦ": "w",
                            "ｗ": "w",
                            "�?": "w",
                            ẃ: "w",
                            ŵ: "w",
                            "ẇ": "w",
                            "ẅ": "w",
                            "ẘ": "w",
                            "ẉ": "w",
                            "ⱳ": "w",
                            "ⓧ": "x",
                            "ｘ": "x",
                            "ẋ": "x",
                            "�?": "x",
                            "ⓨ": "y",
                            "ｙ": "y",
                            "ỳ": "y",
                            "ý": "y",
                            "ŷ": "y",
                            "ỹ": "y",
                            "ȳ": "y",
                            "�?": "y",
                            "ÿ": "y",
                            "ỷ": "y",
                            "ẙ": "y",
                            "ỵ": "y",
                            "ƴ": "y",
                            "�?": "y",
                            "ỿ": "y",
                            "ⓩ": "z",
                            "ｚ": "z",
                            ź: "z",
                            "ẑ": "z",
                            "ż": "z",
                            "ž": "z",
                            "ẓ": "z",
                            "ẕ": "z",
                            "ƶ": "z",
                            "ȥ": "z",
                            "ɀ": "z",
                            "ⱬ": "z",
                            "�?�": "z",
                            "Ά": "Α",
                            Έ: "Ε",
                            "Ή": "Η",
                            Ί: "Ι",
                            Ϊ: "Ι",
                            Ό: "Ο",
                            Ύ: "Υ",
                            "Ϋ": "Υ",
                            "�?": "Ω",
                            "ά": "α",
                            "έ": "ε",
                            "ή": "η",
                            "ί": "ι",
                            ϊ: "ι",
                            "�?": "ι",
                            ό: "ο",
                            "�?": "υ",
                            "ϋ": "υ",
                            "ΰ": "υ",
                            ώ: "ω",
                            "ς": "σ",
                            "’": "'",
                        };
                    }),
                    t.define("select2/data/base", ["../utils"], function (n) {
                        function i(t, e) {
                            i.__super__.constructor.call(this);
                        }
                        return (
                            n.Extend(i, n.Observable),
                            (i.prototype.current = function (t) {
                                throw new Error("The `current` method must be defined in child classes.");
                            }),
                            (i.prototype.query = function (t, e) {
                                throw new Error("The `query` method must be defined in child classes.");
                            }),
                            (i.prototype.bind = function (t, e) {}),
                            (i.prototype.destroy = function () {}),
                            (i.prototype.generateResultId = function (t, e) {
                                var i = t.id + "-result-";
                                return (i += n.generateChars(4)), null !==    e.id ? (i += "-" + e.id.toString()) : (i += "-" + n.generateChars(4)), i;
                            }),
                            i
                        );
                    }),
                    t.define("select2/data/select", ["./base", "../utils", "jquery"], function (t, a, l) {
                        function i(t, e) {
                            (this.$element = t), (this.options = e), i.__super__.constructor.call(this);
                        }
                        return (
                            a.Extend(i, t),
                            (i.prototype.current = function (t) {
                                var i = [],
                                    n = this;
                                this.$element.find(":selected").each(function () {
                                    var t = l(this),
                                        e = n.item(t);
                                    i.push(e);
                                }),
                                    t(i);
                            }),
                            (i.prototype.select = function (s) {
                                var o = this;
                                if (((s.selected = !0), l(s.element).is("option"))) return (s.element.selected = !0), void this.$element.trigger("input").trigger("change");
                                if (this.$element.prop("multiple"))
                                    this.current(function (t) {
                                        var e = [];
                                        (s = [s]).push.apply(s, t);
                                        for (var i = 0; i < s.length; i++) {
                                            var n = s[i].id;
                                            -1 === l.inArray(n, e) && e.push(n);
                                        }
                                        o.$element.val(e), o.$element.trigger("input").trigger("change");
                                    });
                                else {
                                    var t = s.id;
                                    this.$element.val(t), this.$element.trigger("input").trigger("change");
                                }
                            }),
                            (i.prototype.unselect = function (s) {
                                var o = this;
                                if (this.$element.prop("multiple")) {
                                    if (((s.selected = !1), l(s.element).is("option"))) return (s.element.selected = !1), void this.$element.trigger("input").trigger("change");
                                    this.current(function (t) {
                                        for (var e = [], i = 0; i < t.length; i++) {
                                            var n = t[i].id;
                                            n !== s.id && -1 === l.inArray(n, e) && e.push(n);
                                        }
                                        o.$element.val(e), o.$element.trigger("input").trigger("change");
                                    });
                                }
                            }),
                            (i.prototype.bind = function (t, e) {
                                var i = this;
                                (this.container = t).on("select", function (t) {
                                    i.select(t.data);
                                }),
                                    t.on("unselect", function (t) {
                                        i.unselect(t.data);
                                    });
                            }),
                            (i.prototype.destroy = function () {
                                this.$element.find("*").each(function () {
                                    a.RemoveData(this);
                                });
                            }),
                            (i.prototype.query = function (n, t) {
                                var s = [],
                                    o = this;
                                this.$element.children().each(function () {
                                    var t = l(this);
                                    if (t.is("option") || t.is("optgroup")) {
                                        var e = o.item(t),
                                            i = o.matches(n, e);
                                        null !== i && s.push(i);
                                    }
                                }),
                                    t({ results: s });
                            }),
                            (i.prototype.addOptions = function (t) {
                                a.appendMany(this.$element, t);
                            }),
                            (i.prototype.option = function (t) {
                                var e;
                                t.children ? ((e = document.createElement("optgroup")).label = t.text) : void 0 !== (e = document.createElement("option")).textContent ? (e.textContent = t.text) : (e.innerText = t.text),
                                    void 0 !== t.id && (e.value = t.id),
                                    t.disabled && (e.disabled = !0),
                                    t.selected && (e.selected = !0),
                                    t.title && (e.title = t.title);
                                var i = l(e),
                                    n = this._normalizeItem(t);
                                return (n.element = e), a.StoreData(e, "data", n), i;
                            }),
                            (i.prototype.item = function (t) {
                                var e = {};
                                if (null !==    (e = a.GetData(t[0], "data"))) return e;
                                if (t.is("option")) e = { id: t.val(), text: t.text(), disabled: t.prop("disabled"), selected: t.prop("selected"), title: t.prop("title") };
                                else if (t.is("optgroup")) {
                                    e = { text: t.prop("label"), children: [], title: t.prop("title") };
                                    for (var i = t.children("option"), n = [], s = 0; s < i.length; s++) {
                                        var o = l(i[s]),
                                            r = this.item(o);
                                        n.push(r);
                                    }
                                    e.children = n;
                                }
                                return ((e = this._normalizeItem(e)).element = t[0]), a.StoreData(t[0], "data", e), e;
                            }),
                            (i.prototype._normalizeItem = function (t) {
                                t !== Object(t) && (t = { id: t, text: t });
                                return (
                                    null !==    (t = l.extend({}, { text: "" }, t)).id && (t.id = t.id.toString()),
                                    null !==    t.text && (t.text = t.text.toString()),
                                    null === t._resultId && t.id && null !==    this.container && (t._resultId = this.generateResultId(this.container, t)),
                                    l.extend({}, { selected: !1, disabled: !1 }, t)
                                );
                            }),
                            (i.prototype.matches = function (t, e) {
                                return this.options.get("matcher")(t, e);
                            }),
                            i
                        );
                    }),
                    t.define("select2/data/array", ["./select", "../utils", "jquery"], function (t, f, g) {
                        function n(t, e) {
                            (this._dataToConvert = e.get("data") || []), n.__super__.constructor.call(this, t, e);
                        }
                        return (
                            f.Extend(n, t),
                            (n.prototype.bind = function (t, e) {
                                n.__super__.bind.call(this, t, e), this.addOptions(this.convertToOptions(this._dataToConvert));
                            }),
                            (n.prototype.select = function (i) {
                                var t = this.$element.find("option").filter(function (t, e) {
                                    return e.value === i.id.toString();
                                });
                                0 === t.length && ((t = this.option(i)), this.addOptions(t)), n.__super__.select.call(this, i);
                            }),
                            (n.prototype.convertToOptions = function (t) {
                                var e = this,
                                    i = this.$element.find("option"),
                                    n = i
                                        .map(function () {
                                            return e.item(g(this)).id;
                                        })
                                        .get(),
                                    s = [];
                                function o(t) {
                                    return function () {
                                        return g(this).val() === t.id;
                                    };
                                }
                                for (var r = 0; r < t.length; r++) {
                                    var a = this._normalizeItem(t[r]);
                                    if (0 <= g.inArray(a.id, n)) {
                                        var l = i.filter(o(a)),
                                            c = this.item(l),
                                            h = g.extend(!0, {}, a, c),
                                            u = this.option(h);
                                        l.replaceWith(u);
                                    } else {
                                        var d = this.option(a);
                                        if (a.children) {
                                            var p = this.convertToOptions(a.children);
                                            f.appendMany(d, p);
                                        }
                                        s.push(d);
                                    }
                                }
                                return s;
                            }),
                            n
                        );
                    }),
                    t.define("select2/data/ajax", ["./array", "../utils", "jquery"], function (t, e, o) {
                        function i(t, e) {
                            (this.ajaxOptions = this._applyDefaults(e.get("ajax"))), null !==    this.ajaxOptions.processResults && (this.processResults = this.ajaxOptions.processResults), i.__super__.constructor.call(this, t, e);
                        }
                        return (
                            e.Extend(i, t),
                            (i.prototype._applyDefaults = function (t) {
                                var e = {
                                    data: function (t) {
                                        return o.extend({}, t, { q: t.term });
                                    },
                                    transport: function (t, e, i) {
                                        var n = o.ajax(t);
                                        return n.then(e), n.fail(i), n;
                                    },
                                };
                                return o.extend({}, e, t, !0);
                            }),
                            (i.prototype.processResults = function (t) {
                                return t;
                            }),
                            (i.prototype.query = function (i, n) {
                                var s = this;
                                null !==    this._request && (o.isFunction(this._request.abort) && this._request.abort(), (this._request = null));
                                var e = o.extend({ type: "GET" }, this.ajaxOptions);
                                function t() {
                                    var t = e.transport(
                                        e,
                                        function (t) {
                                            var e = s.processResults(t, i);
                                            s.options.get("debug") &&
                                                window.console &&
                                                console.error &&
                                                ((e && e.results && o.isArray(e.results)) || console.error("Select2: The AJAX results did not return an array in the `results` key of the response.")),
                                                n(e);
                                        },
                                        function () {
                                            ("status" in t && (0 === t.status || "0" === t.status)) || s.trigger("results:message", { message: "errorLoading" });
                                        }
                                    );
                                    s._request = t;
                                }
                                "function" === typeof e.url && (e.url = e.url.call(this.$element, i)),
                                    "function" === typeof e.data && (e.data = e.data.call(this.$element, i)),
                                    this.ajaxOptions.delay && null !==    i.term ? (this._queryTimeout && window.clearTimeout(this._queryTimeout), (this._queryTimeout = window.setTimeout(t, this.ajaxOptions.delay))) : t();
                            }),
                            i
                        );
                    }),
                    t.define("select2/data/tags", ["jquery"], function (h) {
                        function t(t, e, i) {
                            var n = i.get("tags"),
                                s = i.get("createTag");
                            void 0 !== s && (this.createTag = s);
                            var o = i.get("insertTag");
                            if ((void 0 !== o && (this.insertTag = o), t.call(this, e, i), h.isArray(n)))
                                for (var r = 0; r < n.length; r++) {
                                    var a = n[r],
                                        l = this._normalizeItem(a),
                                        c = this.option(l);
                                    this.$element.append(c);
                                }
                        }
                        return (
                            (t.prototype.query = function (t, c, h) {
                                var u = this;
                                this._removeOldTags(),
                                    null !==    c.term && null === c.page
                                        ? t.call(this, c, function t(e, i) {
                                              for (var n = e.results, s = 0; s < n.length; s++) {
                                                  var o = n[s],
                                                      r = null !==    o.children && !t({ results: o.children }, !0);
                                                  if ((o.text || "").toUpperCase() === (c.term || "").toUpperCase() || r) return !i && ((e.data = n), void h(e));
                                              }
                                              if (i) return !0;
                                              var a = u.createTag(c);
                                              if (null !==    a) {
                                                  var l = u.option(a);
                                                  l.attr("data-select2-tag", !0), u.addOptions([l]), u.insertTag(n, a);
                                              }
                                              (e.results = n), h(e);
                                          })
                                        : t.call(this, c, h);
                            }),
                            (t.prototype.createTag = function (t, e) {
                                var i = h.trim(e.term);
                                return "" === i ? null : { id: i, text: i };
                            }),
                            (t.prototype.insertTag = function (t, e, i) {
                                e.unshift(i);
                            }),
                            (t.prototype._removeOldTags = function (t) {
                                this.$element.find("option[data-select2-tag]").each(function () {
                                    this.selected || h(this).remove();
                                });
                            }),
                            t
                        );
                    }),
                    t.define("select2/data/tokenizer", ["jquery"], function (u) {
                        function t(t, e, i) {
                            var n = i.get("tokenizer");
                            void 0 !== n && (this.tokenizer = n), t.call(this, e, i);
                        }
                        return (
                            (t.prototype.bind = function (t, e, i) {
                                t.call(this, e, i), (this.$search = e.dropdown.$search || e.selection.$search || i.find(".select2-search__field"));
                            }),
                            (t.prototype.query = function (t, e, i) {
                                var s = this;
                                e.term = e.term || "";
                                var n = this.tokenizer(e, this.options, function (t) {
                                    var e,
                                        i = s._normalizeItem(t);
                                    if (
                                        !s.$element.find("option").filter(function () {
                                            return u(this).val() === i.id;
                                        }).length
                                    ) {
                                        var n = s.option(i);
                                        n.attr("data-select2-tag", !0), s._removeOldTags(), s.addOptions([n]);
                                    }
                                    (e = i), s.trigger("select", { data: e });
                                });
                                n.term !== e.term && (this.$search.length && (this.$search.val(n.term), this.$search.trigger("focus")), (e.term = n.term)), t.call(this, e, i);
                            }),
                            (t.prototype.tokenizer = function (t, e, i, n) {
                                for (
                                    var s = i.get("tokenSeparators") || [],
                                        o = e.term,
                                        r = 0,
                                        a =
                                            this.createTag ||
                                            function (t) {
                                                return { id: t.term, text: t.term };
                                            };
                                    r < o.length;

                                ) {
                                    var l = o[r];
                                    if (-1 !== u.inArray(l, s)) {
                                        var c = o.substr(0, r),
                                            h = a(u.extend({}, e, { term: c }));
                                        null !==    h ? (n(h), (o = o.substr(r + 1) || ""), (r = 0)) : r++;
                                    } else r++;
                                }
                                return { term: o };
                            }),
                            t
                        );
                    }),
                    t.define("select2/data/minimumInputLength", [], function () {
                        function t(t, e, i) {
                            (this.minimumInputLength = i.get("minimumInputLength")), t.call(this, e, i);
                        }
                        return (
                            (t.prototype.query = function (t, e, i) {
                                (e.term = e.term || ""),
                                    e.term.length < this.minimumInputLength ? this.trigger("results:message", { message: "inputTooShort", args: { minimum: this.minimumInputLength, input: e.term, params: e } }) : t.call(this, e, i);
                            }),
                            t
                        );
                    }),
                    t.define("select2/data/maximumInputLength", [], function () {
                        function t(t, e, i) {
                            (this.maximumInputLength = i.get("maximumInputLength")), t.call(this, e, i);
                        }
                        return (
                            (t.prototype.query = function (t, e, i) {
                                (e.term = e.term || ""),
                                    0 < this.maximumInputLength && e.term.length > this.maximumInputLength
                                        ? this.trigger("results:message", { message: "inputTooLong", args: { maximum: this.maximumInputLength, input: e.term, params: e } })
                                        : t.call(this, e, i);
                            }),
                            t
                        );
                    }),
                    t.define("select2/data/maximumSelectionLength", [], function () {
                        function t(t, e, i) {
                            (this.maximumSelectionLength = i.get("maximumSelectionLength")), t.call(this, e, i);
                        }
                        return (
                            (t.prototype.bind = function (t, e, i) {
                                var n = this;
                                t.call(this, e, i),
                                    e.on("select", function () {
                                        n._checkIfMaximumSelected();
                                    });
                            }),
                            (t.prototype.query = function (t, e, i) {
                                var n = this;
                                this._checkIfMaximumSelected(function () {
                                    t.call(n, e, i);
                                });
                            }),
                            (t.prototype._checkIfMaximumSelected = function (t, i) {
                                var n = this;
                                this.current(function (t) {
                                    var e = null !==    t ? t.length : 0;
                                    0 < n.maximumSelectionLength && e >= n.maximumSelectionLength ? n.trigger("results:message", { message: "maximumSelected", args: { maximum: n.maximumSelectionLength } }) : i && i();
                                });
                            }),
                            t
                        );
                    }),
                    t.define("select2/dropdown", ["jquery", "./utils"], function (e, t) {
                        function i(t, e) {
                            (this.$element = t), (this.options = e), i.__super__.constructor.call(this);
                        }
                        return (
                            t.Extend(i, t.Observable),
                            (i.prototype.render = function () {
                                var t = e('<span class="select2-dropdown"><span class="select2-results"></span></span>');
                                return t.attr("dir", this.options.get("dir")), (this.$dropdown = t);
                            }),
                            (i.prototype.bind = function () {}),
                            (i.prototype.position = function (t, e) {}),
                            (i.prototype.destroy = function () {
                                this.$dropdown.remove();
                            }),
                            i
                        );
                    }),
                    t.define("select2/dropdown/search", ["jquery", "../utils"], function (o, t) {
                        function e() {}
                        return (
                            (e.prototype.render = function (t) {
                                var e = t.call(this),
                                    i = o(
                                        '<span class="select2-search select2-search--dropdown"><input class="select2-search__field" type="search" tabindex="-1" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" role="searchbox" aria-autocomplete="list" /></span>'
                                    );
                                return (this.$searchContainer = i), (this.$search = i.find("input")), e.prepend(i), e;
                            }),
                            (e.prototype.bind = function (t, e, i) {
                                var n = this,
                                    s = e.id + "-results";
                                t.call(this, e, i),
                                    this.$search.on("keydown", function (t) {
                                        n.trigger("keypress", t), (n._keyUpPrevented = t.isDefaultPrevented());
                                    }),
                                    this.$search.on("input", function (t) {
                                        o(this).off("keyup");
                                    }),
                                    this.$search.on("keyup input", function (t) {
                                        n.handleSearch(t);
                                    }),
                                    e.on("open", function () {
                                        n.$search.attr("tabindex", 0),
                                            n.$search.attr("aria-controls", s),
                                            n.$search.trigger("focus"),
                                            window.setTimeout(function () {
                                                n.$search.trigger("focus");
                                            }, 0);
                                    }),
                                    e.on("close", function () {
                                        n.$search.attr("tabindex", -1), n.$search.removeAttr("aria-controls"), n.$search.removeAttr("aria-activedescendant"), n.$search.val(""), n.$search.trigger("blur");
                                    }),
                                    e.on("focus", function () {
                                        e.isOpen() || n.$search.trigger("focus");
                                    }),
                                    e.on("results:all", function (t) {
                                        (null !==    t.query.term && "" !== t.query.term) || (n.showSearch(t) ? n.$searchContainer.removeClass("select2-search--hide") : n.$searchContainer.addClass("select2-search--hide"));
                                    }),
                                    e.on("results:focus", function (t) {
                                        t.data._resultId ? n.$search.attr("aria-activedescendant", t.data._resultId) : n.$search.removeAttr("aria-activedescendant");
                                    });
                            }),
                            (e.prototype.handleSearch = function (t) {
                                if (!this._keyUpPrevented) {
                                    var e = this.$search.val();
                                    this.trigger("query", { term: e });
                                }
                                this._keyUpPrevented = !1;
                            }),
                            (e.prototype.showSearch = function (t, e) {
                                return !0;
                            }),
                            e
                        );
                    }),
                    t.define("select2/dropdown/hidePlaceholder", [], function () {
                        function t(t, e, i, n) {
                            (this.placeholder = this.normalizePlaceholder(i.get("placeholder"))), t.call(this, e, i, n);
                        }
                        return (
                            (t.prototype.append = function (t, e) {
                                (e.results = this.removePlaceholder(e.results)), t.call(this, e);
                            }),
                            (t.prototype.normalizePlaceholder = function (t, e) {
                                return "string" === typeof e && (e = { id: "", text: e }), e;
                            }),
                            (t.prototype.removePlaceholder = function (t, e) {
                                for (var i = e.slice(0), n = e.length - 1; 0 <= n; n--) {
                                    var s = e[n];
                                    this.placeholder.id === s.id && i.splice(n, 1);
                                }
                                return i;
                            }),
                            t
                        );
                    }),
                    t.define("select2/dropdown/infiniteScroll", ["jquery"], function (i) {
                        function t(t, e, i, n) {
                            (this.lastParams = {}), t.call(this, e, i, n), (this.$loadingMore = this.createLoadingMore()), (this.loading = !1);
                        }
                        return (
                            (t.prototype.append = function (t, e) {
                                this.$loadingMore.remove(), (this.loading = !1), t.call(this, e), this.showLoadingMore(e) && (this.$results.append(this.$loadingMore), this.loadMoreIfNeeded());
                            }),
                            (t.prototype.bind = function (t, e, i) {
                                var n = this;
                                t.call(this, e, i),
                                    e.on("query", function (t) {
                                        (n.lastParams = t), (n.loading = !0);
                                    }),
                                    e.on("query:append", function (t) {
                                        (n.lastParams = t), (n.loading = !0);
                                    }),
                                    this.$results.on("scroll", this.loadMoreIfNeeded.bind(this));
                            }),
                            (t.prototype.loadMoreIfNeeded = function () {
                                var t = i.contains(document.documentElement, this.$loadingMore[0]);
                                if (!this.loading && t) {
                                    var e = this.$results.offset().top + this.$results.outerHeight(!1);
                                    this.$loadingMore.offset().top + this.$loadingMore.outerHeight(!1) <= e + 50 && this.loadMore();
                                }
                            }),
                            (t.prototype.loadMore = function () {
                                this.loading = !0;
                                var t = i.extend({}, { page: 1 }, this.lastParams);
                                t.page++, this.trigger("query:append", t);
                            }),
                            (t.prototype.showLoadingMore = function (t, e) {
                                return e.pagination && e.pagination.more;
                            }),
                            (t.prototype.createLoadingMore = function () {
                                var t = i('<li class="select2-results__option select2-results__option--load-more"role="option" aria-disabled="true"></li>'),
                                    e = this.options.get("translations").get("loadingMore");
                                return t.html(e(this.lastParams)), t;
                            }),
                            t
                        );
                    }),
                    t.define("select2/dropdown/attachBody", ["jquery", "../utils"], function (f, a) {
                        function t(t, e, i) {
                            (this.$dropdownParent = f(i.get("dropdownParent") || document.body)), t.call(this, e, i);
                        }
                        return (
                            (t.prototype.bind = function (t, e, i) {
                                var n = this;
                                t.call(this, e, i),
                                    e.on("open", function () {
                                        n._showDropdown(), n._attachPositioningHandler(e), n._bindContainerResultHandlers(e);
                                    }),
                                    e.on("close", function () {
                                        n._hideDropdown(), n._detachPositioningHandler(e);
                                    }),
                                    this.$dropdownContainer.on("mousedown", function (t) {
                                        t.stopPropagation();
                                    });
                            }),
                            (t.prototype.destroy = function (t) {
                                t.call(this), this.$dropdownContainer.remove();
                            }),
                            (t.prototype.position = function (t, e, i) {
                                e.attr("class", i.attr("class")), e.removeClass("select2"), e.addClass("select2-container--open"), e.css({ position: "absolute", top: -999999 }), (this.$container = i);
                            }),
                            (t.prototype.render = function (t) {
                                var e = f("<span></span>"),
                                    i = t.call(this);
                                return e.append(i), (this.$dropdownContainer = e);
                            }),
                            (t.prototype._hideDropdown = function (t) {
                                this.$dropdownContainer.detach();
                            }),
                            (t.prototype._bindContainerResultHandlers = function (t, e) {
                                if (!this._containerResultsHandlersBound) {
                                    var i = this;
                                    e.on("results:all", function () {
                                        i._positionDropdown(), i._resizeDropdown();
                                    }),
                                        e.on("results:append", function () {
                                            i._positionDropdown(), i._resizeDropdown();
                                        }),
                                        e.on("results:message", function () {
                                            i._positionDropdown(), i._resizeDropdown();
                                        }),
                                        e.on("select", function () {
                                            i._positionDropdown(), i._resizeDropdown();
                                        }),
                                        e.on("unselect", function () {
                                            i._positionDropdown(), i._resizeDropdown();
                                        }),
                                        (this._containerResultsHandlersBound = !0);
                                }
                            }),
                            (t.prototype._attachPositioningHandler = function (t, e) {
                                var i = this,
                                    n = "scroll.select2." + e.id,
                                    s = "resize.select2." + e.id,
                                    o = "orientationchange.select2." + e.id,
                                    r = this.$container.parents().filter(a.hasScroll);
                                r.each(function () {
                                    a.StoreData(this, "select2-scroll-position", { x: f(this).scrollLeft(), y: f(this).scrollTop() });
                                }),
                                    r.on(n, function (t) {
                                        var e = a.GetData(this, "select2-scroll-position");
                                        f(this).scrollTop(e.y);
                                    }),
                                    f(window).on(n + " " + s + " " + o, function (t) {
                                        i._positionDropdown(), i._resizeDropdown();
                                    });
                            }),
                            (t.prototype._detachPositioningHandler = function (t, e) {
                                var i = "scroll.select2." + e.id,
                                    n = "resize.select2." + e.id,
                                    s = "orientationchange.select2." + e.id;
                                this.$container.parents().filter(a.hasScroll).off(i), f(window).off(i + " " + n + " " + s);
                            }),
                            (t.prototype._positionDropdown = function () {
                                var t = f(window),
                                    e = this.$dropdown.hasClass("select2-dropdown--above"),
                                    i = this.$dropdown.hasClass("select2-dropdown--below"),
                                    n = null,
                                    s = this.$container.offset();
                                s.bottom = s.top + this.$container.outerHeight(!1);
                                var o = { height: this.$container.outerHeight(!1) };
                                (o.top = s.top), (o.bottom = s.top + o.height);
                                var r = this.$dropdown.outerHeight(!1),
                                    a = t.scrollTop(),
                                    l = t.scrollTop() + t.height(),
                                    c = a < s.top - r,
                                    h = l > s.bottom + r,
                                    u = { left: s.left, top: o.bottom },
                                    d = this.$dropdownParent;
                                "static" === d.css("position") && (d = d.offsetParent());
                                var p = { top: 0, left: 0 };
                                (f.contains(document.body, d[0]) || d[0].isConnected) && (p = d.offset()),
                                    (u.top -= p.top),
                                    (u.left -= p.left),
                                    e || i || (n = "below"),
                                    h || !c || e ? !c && h && e && (n = "below") : (n = "above"),
                                    ("above" === n || (e && "below" !== n)) && (u.top = o.top - p.top - r),
                                    null !==    n &&
                                        (this.$dropdown.removeClass("select2-dropdown--below select2-dropdown--above").addClass("select2-dropdown--" + n),
                                        this.$container.removeClass("select2-container--below select2-container--above").addClass("select2-container--" + n)),
                                    this.$dropdownContainer.css(u);
                            }),
                            (t.prototype._resizeDropdown = function () {
                                var t = { width: this.$container.outerWidth(!1) + "px" };
                                this.options.get("dropdownAutoWidth") && ((t.minWidth = t.width), (t.position = "relative"), (t.width = "auto")), this.$dropdown.css(t);
                            }),
                            (t.prototype._showDropdown = function (t) {
                                this.$dropdownContainer.appendTo(this.$dropdownParent), this._positionDropdown(), this._resizeDropdown();
                            }),
                            t
                        );
                    }),
                    t.define("select2/dropdown/minimumResultsForSearch", [], function () {
                        function t(t, e, i, n) {
                            (this.minimumResultsForSearch = i.get("minimumResultsForSearch")), this.minimumResultsForSearch < 0 && (this.minimumResultsForSearch = 1 / 0), t.call(this, e, i, n);
                        }
                        return (
                            (t.prototype.showSearch = function (t, e) {
                                return (
                                    !(
                                        (function t(e) {
                                            for (var i = 0, n = 0; n < e.length; n++) {
                                                var s = e[n];
                                                s.children ? (i += t(s.children)) : i++;
                                            }
                                            return i;
                                        })(e.data.results) < this.minimumResultsForSearch
                                    ) && t.call(this, e)
                                );
                            }),
                            t
                        );
                    }),
                    t.define("select2/dropdown/selectOnClose", ["../utils"], function (o) {
                        function t() {}
                        return (
                            (t.prototype.bind = function (t, e, i) {
                                var n = this;
                                t.call(this, e, i),
                                    e.on("close", function (t) {
                                        n._handleSelectOnClose(t);
                                    });
                            }),
                            (t.prototype._handleSelectOnClose = function (t, e) {
                                if (e && null !==    e.originalSelect2Event) {
                                    var i = e.originalSelect2Event;
                                    if ("select" === i._type || "unselect" === i._type) return;
                                }
                                var n = this.getHighlightedResults();
                                if (!(n.length < 1)) {
                                    var s = o.GetData(n[0], "data");
                                    (null !==    s.element && s.element.selected) || (null === s.element && s.selected) || this.trigger("select", { data: s });
                                }
                            }),
                            t
                        );
                    }),
                    t.define("select2/dropdown/closeOnSelect", [], function () {
                        function t() {}
                        return (
                            (t.prototype.bind = function (t, e, i) {
                                var n = this;
                                t.call(this, e, i),
                                    e.on("select", function (t) {
                                        n._selectTriggered(t);
                                    }),
                                    e.on("unselect", function (t) {
                                        n._selectTriggered(t);
                                    });
                            }),
                            (t.prototype._selectTriggered = function (t, e) {
                                var i = e.originalEvent;
                                (i && (i.ctrlKey || i.metaKey)) || this.trigger("close", { originalEvent: i, originalSelect2Event: e });
                            }),
                            t
                        );
                    }),
                    t.define("select2/i18n/en", [], function () {
                        return {
                            errorLoading: function () {
                                return "The results could not be loaded.";
                            },
                            inputTooLong: function (t) {
                                var e = t.input.length - t.maximum,
                                    i = "Please delete " + e + " character";
                                return 1 !==    e && (i += "s"), i;
                            },
                            inputTooShort: function (t) {
                                return "Please enter " + (t.minimum - t.input.length) + " or more characters";
                            },
                            loadingMore: function () {
                                return "Loading more results…";
                            },
                            maximumSelected: function (t) {
                                var e = "You can only select " + t.maximum + " item";
                                return 1 !==    t.maximum && (e += "s"), e;
                            },
                            noResults: function () {
                                return "No results found";
                            },
                            searching: function () {
                                return "Searching…";
                            },
                            removeAllItems: function () {
                                return "Remove all items";
                            },
                        };
                    }),
                    t.define(
                        "select2/defaults",
                        [
                            "jquery",
                            "require",
                            "./results",
                            "./selection/single",
                            "./selection/multiple",
                            "./selection/placeholder",
                            "./selection/allowClear",
                            "./selection/search",
                            "./selection/eventRelay",
                            "./utils",
                            "./translation",
                            "./diacritics",
                            "./data/select",
                            "./data/array",
                            "./data/ajax",
                            "./data/tags",
                            "./data/tokenizer",
                            "./data/minimumInputLength",
                            "./data/maximumInputLength",
                            "./data/maximumSelectionLength",
                            "./dropdown",
                            "./dropdown/search",
                            "./dropdown/hidePlaceholder",
                            "./dropdown/infiniteScroll",
                            "./dropdown/attachBody",
                            "./dropdown/minimumResultsForSearch",
                            "./dropdown/selectOnClose",
                            "./dropdown/closeOnSelect",
                            "./i18n/en",
                        ],
                        function (c, h, u, d, p, f, g, m, v, y, r, e, b, w, _, x, C, D, k, T, S, E, A, P, $, I, z, O, t) {
                            function i() {
                                this.reset();
                            }
                            return (
                                (i.prototype.apply = function (t) {
                                    if (null === (t = c.extend(!0, {}, this.defaults, t)).dataAdapter) {
                                        if (
                                            (null !==    t.ajax ? (t.dataAdapter = _) : null !==    t.data ? (t.dataAdapter = w) : (t.dataAdapter = b),
                                            0 < t.minimumInputLength && (t.dataAdapter = y.Decorate(t.dataAdapter, D)),
                                            0 < t.maximumInputLength && (t.dataAdapter = y.Decorate(t.dataAdapter, k)),
                                            0 < t.maximumSelectionLength && (t.dataAdapter = y.Decorate(t.dataAdapter, T)),
                                            t.tags && (t.dataAdapter = y.Decorate(t.dataAdapter, x)),
                                            (null === t.tokenSeparators && null === t.tokenizer) || (t.dataAdapter = y.Decorate(t.dataAdapter, C)),
                                            null !==    t.query)
                                        ) {
                                            var e = h(t.amdBase + "compat/query");
                                            t.dataAdapter = y.Decorate(t.dataAdapter, e);
                                        }
                                        if (null !==    t.initSelection) {
                                            var i = h(t.amdBase + "compat/initSelection");
                                            t.dataAdapter = y.Decorate(t.dataAdapter, i);
                                        }
                                    }
                                    if (
                                        (null === t.resultsAdapter &&
                                            ((t.resultsAdapter = u),
                                            null !==    t.ajax && (t.resultsAdapter = y.Decorate(t.resultsAdapter, P)),
                                            null !==    t.placeholder && (t.resultsAdapter = y.Decorate(t.resultsAdapter, A)),
                                            t.selectOnClose && (t.resultsAdapter = y.Decorate(t.resultsAdapter, z))),
                                        null === t.dropdownAdapter)
                                    ) {
                                        if (t.multiple) t.dropdownAdapter = S;
                                        else {
                                            var n = y.Decorate(S, E);
                                            t.dropdownAdapter = n;
                                        }
                                        if (
                                            (0 !== t.minimumResultsForSearch && (t.dropdownAdapter = y.Decorate(t.dropdownAdapter, I)),
                                            t.closeOnSelect && (t.dropdownAdapter = y.Decorate(t.dropdownAdapter, O)),
                                            null !==    t.dropdownCssClass || null !==    t.dropdownCss || null !==    t.adaptDropdownCssClass)
                                        ) {
                                            var s = h(t.amdBase + "compat/dropdownCss");
                                            t.dropdownAdapter = y.Decorate(t.dropdownAdapter, s);
                                        }
                                        t.dropdownAdapter = y.Decorate(t.dropdownAdapter, $);
                                    }
                                    if (null === t.selectionAdapter) {
                                        if (
                                            (t.multiple ? (t.selectionAdapter = p) : (t.selectionAdapter = d),
                                            null !==    t.placeholder && (t.selectionAdapter = y.Decorate(t.selectionAdapter, f)),
                                            t.allowClear && (t.selectionAdapter = y.Decorate(t.selectionAdapter, g)),
                                            t.multiple && (t.selectionAdapter = y.Decorate(t.selectionAdapter, m)),
                                            null !==    t.containerCssClass || null !==    t.containerCss || null !==    t.adaptContainerCssClass)
                                        ) {
                                            var o = h(t.amdBase + "compat/containerCss");
                                            t.selectionAdapter = y.Decorate(t.selectionAdapter, o);
                                        }
                                        t.selectionAdapter = y.Decorate(t.selectionAdapter, v);
                                    }
                                    (t.language = this._resolveLanguage(t.language)), t.language.push("en");
                                    for (var r = [], a = 0; a < t.language.length; a++) {
                                        var l = t.language[a];
                                        -1 === r.indexOf(l) && r.push(l);
                                    }
                                    return (t.language = r), (t.translations = this._processTranslations(t.language, t.debug)), t;
                                }),
                                (i.prototype.reset = function () {
                                    function a(t) {
                                        return t.replace(/[^\u0000-\u007E]/g, function (t) {
                                            return e[t] || t;
                                        });
                                    }
                                    this.defaults = {
                                        amdBase: "./",
                                        amdLanguageBase: "./i18n/",
                                        closeOnSelect: !0,
                                        debug: !1,
                                        dropdownAutoWidth: !1,
                                        escapeMarkup: y.escapeMarkup,
                                        language: {},
                                        matcher: function t(e, i) {
                                            if ("" === c.trim(e.term)) return i;
                                            if (i.children && 0 < i.children.length) {
                                                for (var n = c.extend(!0, {}, i), s = i.children.length - 1; 0 <= s; s--) null === t(e, i.children[s]) && n.children.splice(s, 1);
                                                return 0 < n.children.length ? n : t(e, n);
                                            }
                                            var o = a(i.text).toUpperCase(),
                                                r = a(e.term).toUpperCase();
                                            return -1 < o.indexOf(r) ? i : null;
                                        },
                                        minimumInputLength: 0,
                                        maximumInputLength: 0,
                                        maximumSelectionLength: 0,
                                        minimumResultsForSearch: 0,
                                        selectOnClose: !1,
                                        scrollAfterSelect: !1,
                                        sorter: function (t) {
                                            return t;
                                        },
                                        templateResult: function (t) {
                                            return t.text;
                                        },
                                        templateSelection: function (t) {
                                            return t.text;
                                        },
                                        theme: "default",
                                        width: "resolve",
                                    };
                                }),
                                (i.prototype.applyFromElement = function (t, e) {
                                    var i = t.language,
                                        n = this.defaults.language,
                                        s = e.prop("lang"),
                                        o = e.closest("[lang]").prop("lang"),
                                        r = Array.prototype.concat.call(this._resolveLanguage(s), this._resolveLanguage(i), this._resolveLanguage(n), this._resolveLanguage(o));
                                    return (t.language = r), t;
                                }),
                                (i.prototype._resolveLanguage = function (t) {
                                    if (!t) return [];
                                    if (c.isEmptyObject(t)) return [];
                                    if (c.isPlainObject(t)) return [t];
                                    var e;
                                    e = c.isArray(t) ? t : [t];
                                    for (var i = [], n = 0; n < e.length; n++)
                                        if ((i.push(e[n]), "string" === typeof e[n] && 0 < e[n].indexOf("-"))) {
                                            var s = e[n].split("-")[0];
                                            i.push(s);
                                        }
                                    return i;
                                }),
                                (i.prototype._processTranslations = function (t, e) {
                                    for (var i = new r(), n = 0; n < t.length; n++) {
                                        var s = new r(),
                                            o = t[n];
                                        if ("string" === typeof o)
                                            try {
                                                s = r.loadPath(o);
                                            } catch (t) {
                                                try {
                                                    (o = this.defaults.amdLanguageBase + o), (s = r.loadPath(o));
                                                } catch (t) {
                                                    e && window.console && console.warn && console.warn('Select2: The language file for "' + o + '" could not be automatically loaded. A fallback will be used instead.');
                                                }
                                            }
                                        else s = c.isPlainObject(o) ? new r(o) : o;
                                        i.extend(s);
                                    }
                                    return i;
                                }),
                                (i.prototype.set = function (t, e) {
                                    var i = {};
                                    i[c.camelCase(t)] = e;
                                    var n = y._convertData(i);
                                    c.extend(!0, this.defaults, n);
                                }),
                                new i()
                            );
                        }
                    ),
                    t.define("select2/options", ["require", "jquery", "./defaults", "./utils"], function (n, h, s, u) {
                        function t(t, e) {
                            if (((this.options = t), null !==    e && this.fromElement(e), null !==    e && (this.options = s.applyFromElement(this.options, e)), (this.options = s.apply(this.options)), e && e.is("input"))) {
                                var i = n(this.get("amdBase") + "compat/inputData");
                                this.options.dataAdapter = u.Decorate(this.options.dataAdapter, i);
                            }
                        }
                        return (
                            (t.prototype.fromElement = function (t) {
                                var e = ["select2"];
                                null === this.options.multiple && (this.options.multiple = t.prop("multiple")),
                                    null === this.options.disabled && (this.options.disabled = t.prop("disabled")),
                                    null === this.options.dir && (t.prop("dir") ? (this.options.dir = t.prop("dir")) : t.closest("[dir]").prop("dir") ? (this.options.dir = t.closest("[dir]").prop("dir")) : (this.options.dir = "ltr")),
                                    t.prop("disabled", this.options.disabled),
                                    t.prop("multiple", this.options.multiple),
                                    u.GetData(t[0], "select2Tags") &&
                                        (this.options.debug &&
                                            window.console &&
                                            console.warn &&
                                            console.warn('Select2: The `data-select2-tags` attribute has been changed to use the `data-data` and `data-tags="true"` attributes and will be removed in future versions of Select2.'),
                                        u.StoreData(t[0], "data", u.GetData(t[0], "select2Tags")),
                                        u.StoreData(t[0], "tags", !0)),
                                    u.GetData(t[0], "ajaxUrl") &&
                                        (this.options.debug &&
                                            window.console &&
                                            console.warn &&
                                            console.warn("Select2: The `data-ajax-url` attribute has been changed to `data-ajax--url` and support for the old attribute will be removed in future versions of Select2."),
                                        t.attr("ajax--url", u.GetData(t[0], "ajaxUrl")),
                                        u.StoreData(t[0], "ajax-Url", u.GetData(t[0], "ajaxUrl")));
                                var i = {};
                                function n(t, e) {
                                    return e.toUpperCase();
                                }
                                for (var s = 0; s < t[0].attributes.length; s++) {
                                    var o = t[0].attributes[s].name;
                                    if ("data-" === o.substr(0, "data-".length)) {
                                        var r = o.substring("data-".length),
                                            a = u.GetData(t[0], r);
                                        i[r.replace(/-([a-z])/g, n)] = a;
                                    }
                                }
                                h.fn.jquery && "1." === h.fn.jquery.substr(0, 2) && t[0].dataset && (i = h.extend(!0, {}, t[0].dataset, i));
                                var l = h.extend(!0, {}, u.GetData(t[0]), i);
                                for (var c in (l = u._convertData(l))) -1 < h.inArray(c, e) || (h.isPlainObject(this.options[c]) ? h.extend(this.options[c], l[c]) : (this.options[c] = l[c]));
                                return this;
                            }),
                            (t.prototype.get = function (t) {
                                return this.options[t];
                            }),
                            (t.prototype.set = function (t, e) {
                                this.options[t] = e;
                            }),
                            t
                        );
                    }),
                    t.define("select2/core", ["jquery", "./options", "./utils", "./keys"], function (o, c, h, n) {
                        var u = function (t, e) {
                            null !==    h.GetData(t[0], "select2") && h.GetData(t[0], "select2").destroy(), (this.$element = t), (this.id = this._generateId(t)), (e = e || {}), (this.options = new c(e, t)), u.__super__.constructor.call(this);
                            var i = t.attr("tabindex") || 0;
                            h.StoreData(t[0], "old-tabindex", i), t.attr("tabindex", "-1");
                            var n = this.options.get("dataAdapter");
                            this.dataAdapter = new n(t, this.options);
                            var s = this.render();
                            this._placeContainer(s);
                            var o = this.options.get("selectionAdapter");
                            (this.selection = new o(t, this.options)), (this.$selection = this.selection.render()), this.selection.position(this.$selection, s);
                            var r = this.options.get("dropdownAdapter");
                            (this.dropdown = new r(t, this.options)), (this.$dropdown = this.dropdown.render()), this.dropdown.position(this.$dropdown, s);
                            var a = this.options.get("resultsAdapter");
                            (this.results = new a(t, this.options, this.dataAdapter)), (this.$results = this.results.render()), this.results.position(this.$results, this.$dropdown);
                            var l = this;
                            this._bindAdapters(),
                                this._registerDomEvents(),
                                this._registerDataEvents(),
                                this._registerSelectionEvents(),
                                this._registerDropdownEvents(),
                                this._registerResultsEvents(),
                                this._registerEvents(),
                                this.dataAdapter.current(function (t) {
                                    l.trigger("selection:update", { data: t });
                                }),
                                t.addClass("select2-hidden-accessible"),
                                t.attr("aria-hidden", "true"),
                                this._syncAttributes(),
                                h.StoreData(t[0], "select2", this),
                                t.data("select2", this);
                        };
                        return (
                            h.Extend(u, h.Observable),
                            (u.prototype._generateId = function (t) {
                                return "select2-" + (null !==    t.attr("id") ? t.attr("id") : null !==    t.attr("name") ? t.attr("name") + "-" + h.generateChars(2) : h.generateChars(4)).replace(/(:|\.|\[|\]|,)/g, "");
                            }),
                            (u.prototype._placeContainer = function (t) {
                                t.insertAfter(this.$element);
                                var e = this._resolveWidth(this.$element, this.options.get("width"));
                                null !==    e && t.css("width", e);
                            }),
                            (u.prototype._resolveWidth = function (t, e) {
                                var i = /^width:(([-+]?([0-9]*\.)?[0-9]+)(px|em|ex|%|in|cm|mm|pt|pc))/i;
                                if ("resolve" === e) {
                                    var n = this._resolveWidth(t, "style");
                                    return null !==    n ? n : this._resolveWidth(t, "element");
                                }
                                if ("element" === e) {
                                    var s = t.outerWidth(!1);
                                    return s <= 0 ? "auto" : s + "px";
                                }
                                if ("style" !==    e) return "computedstyle" !==    e ? e : window.getComputedStyle(t[0]).width;
                                var o = t.attr("style");
                                if ("string" !==    typeof o) return null;
                                for (var r = o.split(";"), a = 0, l = r.length; a < l; a += 1) {
                                    var c = r[a].replace(/\s/g, "").match(i);
                                    if (null !== c && 1 <= c.length) return c[1];
                                }
                                return null;
                            }),
                            (u.prototype._bindAdapters = function () {
                                this.dataAdapter.bind(this, this.$container), this.selection.bind(this, this.$container), this.dropdown.bind(this, this.$container), this.results.bind(this, this.$container);
                            }),
                            (u.prototype._registerDomEvents = function () {
                                var e = this;
                                this.$element.on("change.select2", function () {
                                    e.dataAdapter.current(function (t) {
                                        e.trigger("selection:update", { data: t });
                                    });
                                }),
                                    this.$element.on("focus.select2", function (t) {
                                        e.trigger("focus", t);
                                    }),
                                    (this._syncA = h.bind(this._syncAttributes, this)),
                                    (this._syncS = h.bind(this._syncSubtree, this)),
                                    this.$element[0].attachEvent && this.$element[0].attachEvent("onpropertychange", this._syncA);
                                var t = window.MutationObserver || window.WebKitMutationObserver || window.MozMutationObserver;
                                null !==    t
                                    ? ((this._observer = new t(function (t) {
                                          e._syncA(), e._syncS(null, t);
                                      })),
                                      this._observer.observe(this.$element[0], { attributes: !0, childList: !0, subtree: !1 }))
                                    : this.$element[0].addEventListener &&
                                      (this.$element[0].addEventListener("DOMAttrModified", e._syncA, !1),
                                      this.$element[0].addEventListener("DOMNodeInserted", e._syncS, !1),
                                      this.$element[0].addEventListener("DOMNodeRemoved", e._syncS, !1));
                            }),
                            (u.prototype._registerDataEvents = function () {
                                var i = this;
                                this.dataAdapter.on("*", function (t, e) {
                                    i.trigger(t, e);
                                });
                            }),
                            (u.prototype._registerSelectionEvents = function () {
                                var i = this,
                                    n = ["toggle", "focus"];
                                this.selection.on("toggle", function () {
                                    i.toggleDropdown();
                                }),
                                    this.selection.on("focus", function (t) {
                                        i.focus(t);
                                    }),
                                    this.selection.on("*", function (t, e) {
                                        -1 === o.inArray(t, n) && i.trigger(t, e);
                                    });
                            }),
                            (u.prototype._registerDropdownEvents = function () {
                                var i = this;
                                this.dropdown.on("*", function (t, e) {
                                    i.trigger(t, e);
                                });
                            }),
                            (u.prototype._registerResultsEvents = function () {
                                var i = this;
                                this.results.on("*", function (t, e) {
                                    i.trigger(t, e);
                                });
                            }),
                            (u.prototype._registerEvents = function () {
                                var i = this;
                                this.on("open", function () {
                                    i.$container.addClass("select2-container--open");
                                }),
                                    this.on("close", function () {
                                        i.$container.removeClass("select2-container--open");
                                    }),
                                    this.on("enable", function () {
                                        i.$container.removeClass("select2-container--disabled");
                                    }),
                                    this.on("disable", function () {
                                        i.$container.addClass("select2-container--disabled");
                                    }),
                                    this.on("blur", function () {
                                        i.$container.removeClass("select2-container--focus");
                                    }),
                                    this.on("query", function (e) {
                                        i.isOpen() || i.trigger("open", {}),
                                            this.dataAdapter.query(e, function (t) {
                                                i.trigger("results:all", { data: t, query: e });
                                            });
                                    }),
                                    this.on("query:append", function (e) {
                                        this.dataAdapter.query(e, function (t) {
                                            i.trigger("results:append", { data: t, query: e });
                                        });
                                    }),
                                    this.on("keypress", function (t) {
                                        var e = t.which;
                                        i.isOpen()
                                            ? e === n.ESC || e === n.TAB || (e === n.UP && t.altKey)
                                                ? (i.close(t), t.preventDefault())
                                                : e === n.ENTER
                                                ? (i.trigger("results:select", {}), t.preventDefault())
                                                : e === n.SPACE && t.ctrlKey
                                                ? (i.trigger("results:toggle", {}), t.preventDefault())
                                                : e === n.UP
                                                ? (i.trigger("results:previous", {}), t.preventDefault())
                                                : e === n.DOWN && (i.trigger("results:next", {}), t.preventDefault())
                                            : (e === n.ENTER || e === n.SPACE || (e === n.DOWN && t.altKey)) && (i.open(), t.preventDefault());
                                    });
                            }),
                            (u.prototype._syncAttributes = function () {
                                this.options.set("disabled", this.$element.prop("disabled")), this.isDisabled() ? (this.isOpen() && this.close(), this.trigger("disable", {})) : this.trigger("enable", {});
                            }),
                            (u.prototype._isChangeMutation = function (t, e) {
                                var i = !1,
                                    n = this;
                                if (!t || !t.target || "OPTION" === t.target.nodeName || "OPTGROUP" === t.target.nodeName) {
                                    if (e)
                                        if (e.addedNodes && 0 < e.addedNodes.length)
                                            for (var s = 0; s < e.addedNodes.length; s++) {
                                                e.addedNodes[s].selected && (i = !0);
                                            }
                                        else
                                            e.removedNodes && 0 < e.removedNodes.length
                                                ? (i = !0)
                                                : o.isArray(e) &&
                                                  o.each(e, function (t, e) {
                                                      if (n._isChangeMutation(t, e)) return !(i = !0);
                                                  });
                                    else i = !0;
                                    return i;
                                }
                            }),
                            (u.prototype._syncSubtree = function (t, e) {
                                var i = this._isChangeMutation(t, e),
                                    n = this;
                                i &&
                                    this.dataAdapter.current(function (t) {
                                        n.trigger("selection:update", { data: t });
                                    });
                            }),
                            (u.prototype.trigger = function (t, e) {
                                var i = u.__super__.trigger,
                                    n = { open: "opening", close: "closing", select: "selecting", unselect: "unselecting", clear: "clearing" };
                                if ((void 0 === e && (e = {}), t in n)) {
                                    var s = n[t],
                                        o = { prevented: !1, name: t, args: e };
                                    if ((i.call(this, s, o), o.prevented)) return void (e.prevented = !0);
                                }
                                i.call(this, t, e);
                            }),
                            (u.prototype.toggleDropdown = function () {
                                this.isDisabled() || (this.isOpen() ? this.close() : this.open());
                            }),
                            (u.prototype.open = function () {
                                this.isOpen() || this.isDisabled() || this.trigger("query", {});
                            }),
                            (u.prototype.close = function (t) {
                                this.isOpen() && this.trigger("close", { originalEvent: t });
                            }),
                            (u.prototype.isEnabled = function () {
                                return !this.isDisabled();
                            }),
                            (u.prototype.isDisabled = function () {
                                return this.options.get("disabled");
                            }),
                            (u.prototype.isOpen = function () {
                                return this.$container.hasClass("select2-container--open");
                            }),
                            (u.prototype.hasFocus = function () {
                                return this.$container.hasClass("select2-container--focus");
                            }),
                            (u.prototype.focus = function (t) {
                                this.hasFocus() || (this.$container.addClass("select2-container--focus"), this.trigger("focus", {}));
                            }),
                            (u.prototype.enable = function (t) {
                                this.options.get("debug") &&
                                    window.console &&
                                    console.warn &&
                                    console.warn('Select2: The `select2("enable")` method has been deprecated and will be removed in later Select2 versions. Use $element.prop("disabled") instead.'),
                                    (null !==    t && 0 !== t.length) || (t = [!0]);
                                var e = !t[0];
                                this.$element.prop("disabled", e);
                            }),
                            (u.prototype.data = function () {
                                this.options.get("debug") &&
                                    0 < arguments.length &&
                                    window.console &&
                                    console.warn &&
                                    console.warn('Select2: Data can no longer be set using `select2("data")`. You should consider setting the value instead using `$element.val()`.');
                                var e = [];
                                return (
                                    this.dataAdapter.current(function (t) {
                                        e = t;
                                    }),
                                    e
                                );
                            }),
                            (u.prototype.val = function (t) {
                                if (
                                    (this.options.get("debug") &&
                                        window.console &&
                                        console.warn &&
                                        console.warn('Select2: The `select2("val")` method has been deprecated and will be removed in later Select2 versions. Use $element.val() instead.'),
                                    null === t || 0 === t.length)
                                )
                                    return this.$element.val();
                                var e = t[0];
                                o.isArray(e) &&
                                    (e = o.map(e, function (t) {
                                        return t.toString();
                                    })),
                                    this.$element.val(e).trigger("input").trigger("change");
                            }),
                            (u.prototype.destroy = function () {
                                this.$container.remove(),
                                    this.$element[0].detachEvent && this.$element[0].detachEvent("onpropertychange", this._syncA),
                                    null !==    this._observer
                                        ? (this._observer.disconnect(), (this._observer = null))
                                        : this.$element[0].removeEventListener &&
                                          (this.$element[0].removeEventListener("DOMAttrModified", this._syncA, !1),
                                          this.$element[0].removeEventListener("DOMNodeInserted", this._syncS, !1),
                                          this.$element[0].removeEventListener("DOMNodeRemoved", this._syncS, !1)),
                                    (this._syncA = null),
                                    (this._syncS = null),
                                    this.$element.off(".select2"),
                                    this.$element.attr("tabindex", h.GetData(this.$element[0], "old-tabindex")),
                                    this.$element.removeClass("select2-hidden-accessible"),
                                    this.$element.attr("aria-hidden", "false"),
                                    h.RemoveData(this.$element[0]),
                                    this.$element.removeData("select2"),
                                    this.dataAdapter.destroy(),
                                    this.selection.destroy(),
                                    this.dropdown.destroy(),
                                    this.results.destroy(),
                                    (this.dataAdapter = null),
                                    (this.selection = null),
                                    (this.dropdown = null),
                                    (this.results = null);
                            }),
                            (u.prototype.render = function () {
                                var t = o('<span class="select2 select2-container"><span class="selection"></span><span class="dropdown-wrapper" aria-hidden="true"></span></span>');
                                return t.attr("dir", this.options.get("dir")), (this.$container = t), this.$container.addClass("select2-container--" + this.options.get("theme")), h.StoreData(t[0], "element", this.$element), t;
                            }),
                            u
                        );
                    }),
                    t.define("select2/compat/utils", ["jquery"], function (r) {
                        return {
                            syncCssClasses: function (t, e, i) {
                                var n,
                                    s,
                                    o = [];
                                (n = r.trim(t.attr("class"))) &&
                                    r((n = "" + n).split(/\s+/)).each(function () {
                                        0 === this.indexOf("select2-") && o.push(this);
                                    }),
                                    (n = r.trim(e.attr("class"))) &&
                                        r((n = "" + n).split(/\s+/)).each(function () {
                                            0 !== this.indexOf("select2-") && null !==    (s = i(this)) && o.push(s);
                                        }),
                                    t.attr("class", o.join(" "));
                            },
                        };
                    }),
                    t.define("select2/compat/containerCss", ["jquery", "./utils"], function (r, a) {
                        function l(t) {
                            return null;
                        }
                        function t() {}
                        return (
                            (t.prototype.render = function (t) {
                                var e = t.call(this),
                                    i = this.options.get("containerCssClass") || "";
                                r.isFunction(i) && (i = i(this.$element));
                                var n = this.options.get("adaptContainerCssClass");
                                if (((n = n || l), -1 !== i.indexOf(":all:"))) {
                                    i = i.replace(":all:", "");
                                    var s = n;
                                    n = function (t) {
                                        var e = s(t);
                                        return null !==    e ? e + " " + t : t;
                                    };
                                }
                                var o = this.options.get("containerCss") || {};
                                return r.isFunction(o) && (o = o(this.$element)), a.syncCssClasses(e, this.$element, n), e.css(o), e.addClass(i), e;
                            }),
                            t
                        );
                    }),
                    t.define("select2/compat/dropdownCss", ["jquery", "./utils"], function (r, a) {
                        function l(t) {
                            return null;
                        }
                        function t() {}
                        return (
                            (t.prototype.render = function (t) {
                                var e = t.call(this),
                                    i = this.options.get("dropdownCssClass") || "";
                                r.isFunction(i) && (i = i(this.$element));
                                var n = this.options.get("adaptDropdownCssClass");
                                if (((n = n || l), -1 !== i.indexOf(":all:"))) {
                                    i = i.replace(":all:", "");
                                    var s = n;
                                    n = function (t) {
                                        var e = s(t);
                                        return null !==    e ? e + " " + t : t;
                                    };
                                }
                                var o = this.options.get("dropdownCss") || {};
                                return r.isFunction(o) && (o = o(this.$element)), a.syncCssClasses(e, this.$element, n), e.css(o), e.addClass(i), e;
                            }),
                            t
                        );
                    }),
                    t.define("select2/compat/initSelection", ["jquery"], function (n) {
                        function t(t, e, i) {
                            i.get("debug") &&
                                window.console &&
                                console.warn &&
                                console.warn(
                                    "Select2: The `initSelection` option has been deprecated in favor of a custom data adapter that overrides the `current` method. This method is now called multiple times instead of a single time when the instance is initialized. Support will be removed for the `initSelection` option in future versions of Select2"
                                ),
                                (this.initSelection = i.get("initSelection")),
                                (this._isInitialized = !1),
                                t.call(this, e, i);
                        }
                        return (
                            (t.prototype.current = function (t, e) {
                                var i = this;
                                this._isInitialized
                                    ? t.call(this, e)
                                    : this.initSelection.call(null, this.$element, function (t) {
                                          (i._isInitialized = !0), n.isArray(t) || (t = [t]), e(t);
                                      });
                            }),
                            t
                        );
                    }),
                    t.define("select2/compat/inputData", ["jquery", "../utils"], function (r, n) {
                        function t(t, e, i) {
                            (this._currentData = []),
                                (this._valueSeparator = i.get("valueSeparator") || ","),
                                "hidden" === e.prop("type") &&
                                    i.get("debug") &&
                                    console &&
                                    console.warn &&
                                    console.warn("Select2: Using a hidden input with Select2 is no longer supported and may stop working in the future. It is recommended to use a `<select>` element instead."),
                                t.call(this, e, i);
                        }
                        return (
                            (t.prototype.current = function (t, e) {
                                function n(t, e) {
                                    var i = [];
                                    return t.selected || -1 !== r.inArray(t.id, e) ? ((t.selected = !0), i.push(t)) : (t.selected = !1), t.children && i.push.apply(i, n(t.children, e)), i;
                                }
                                for (var i = [], s = 0; s < this._currentData.length; s++) {
                                    var o = this._currentData[s];
                                    i.push.apply(i, n(o, this.$element.val().split(this._valueSeparator)));
                                }
                                e(i);
                            }),
                            (t.prototype.select = function (t, e) {
                                if (this.options.get("multiple")) {
                                    var i = this.$element.val();
                                    (i += this._valueSeparator + e.id), this.$element.val(i), this.$element.trigger("input").trigger("change");
                                } else
                                    this.current(function (t) {
                                        r.map(t, function (t) {
                                            t.selected = !1;
                                        });
                                    }),
                                        this.$element.val(e.id),
                                        this.$element.trigger("input").trigger("change");
                            }),
                            (t.prototype.unselect = function (t, s) {
                                var o = this;
                                (s.selected = !1),
                                    this.current(function (t) {
                                        for (var e = [], i = 0; i < t.length; i++) {
                                            var n = t[i];
                                            s.id !==    n.id && e.push(n.id);
                                        }
                                        o.$element.val(e.join(o._valueSeparator)), o.$element.trigger("input").trigger("change");
                                    });
                            }),
                            (t.prototype.query = function (t, e, i) {
                                for (var n = [], s = 0; s < this._currentData.length; s++) {
                                    var o = this._currentData[s],
                                        r = this.matches(e, o);
                                    null !== r && n.push(r);
                                }
                                i({ results: n });
                            }),
                            (t.prototype.addOptions = function (t, e) {
                                var i = r.map(e, function (t) {
                                    return n.GetData(t[0], "data");
                                });
                                this._currentData.push.apply(this._currentData, i);
                            }),
                            t
                        );
                    }),
                    t.define("select2/compat/matcher", ["jquery"], function (r) {
                        return function (o) {
                            return function (t, e) {
                                var i = r.extend(!0, {}, e);
                                if (null === t.term || "" === r.trim(t.term)) return i;
                                if (e.children) {
                                    for (var n = e.children.length - 1; 0 <= n; n--) {
                                        var s = e.children[n];
                                        o(t.term, s.text, s) || i.children.splice(n, 1);
                                    }
                                    if (0 < i.children.length) return i;
                                }
                                return o(t.term, e.text, e) ? i : null;
                            };
                        };
                    }),
                    t.define("select2/compat/query", [], function () {
                        function t(t, e, i) {
                            i.get("debug") &&
                                window.console &&
                                console.warn &&
                                console.warn(
                                    "Select2: The `query` option has been deprecated in favor of a custom data adapter that overrides the `query` method. Support will be removed for the `query` option in future versions of Select2."
                                ),
                                t.call(this, e, i);
                        }
                        return (
                            (t.prototype.query = function (t, e, i) {
                                (e.callback = i), this.options.get("query").call(null, e);
                            }),
                            t
                        );
                    }),
                    t.define("select2/dropdown/attachContainer", [], function () {
                        function t(t, e, i) {
                            t.call(this, e, i);
                        }
                        return (
                            (t.prototype.position = function (t, e, i) {
                                i.find(".dropdown-wrapper").append(e), e.addClass("select2-dropdown--below"), i.addClass("select2-container--below");
                            }),
                            t
                        );
                    }),
                    t.define("select2/dropdown/stopPropagation", [], function () {
                        function t() {}
                        return (
                            (t.prototype.bind = function (t, e, i) {
                                t.call(this, e, i);
                                this.$dropdown.on(
                                    [
                                        "blur",
                                        "change",
                                        "click",
                                        "dblclick",
                                        "focus",
                                        "focusin",
                                        "focusout",
                                        "input",
                                        "keydown",
                                        "keyup",
                                        "keypress",
                                        "mousedown",
                                        "mouseenter",
                                        "mouseleave",
                                        "mousemove",
                                        "mouseover",
                                        "mouseup",
                                        "search",
                                        "touchend",
                                        "touchstart",
                                    ].join(" "),
                                    function (t) {
                                        t.stopPropagation();
                                    }
                                );
                            }),
                            t
                        );
                    }),
                    t.define("select2/selection/stopPropagation", [], function () {
                        function t() {}
                        return (
                            (t.prototype.bind = function (t, e, i) {
                                t.call(this, e, i);
                                this.$selection.on(
                                    [
                                        "blur",
                                        "change",
                                        "click",
                                        "dblclick",
                                        "focus",
                                        "focusin",
                                        "focusout",
                                        "input",
                                        "keydown",
                                        "keyup",
                                        "keypress",
                                        "mousedown",
                                        "mouseenter",
                                        "mouseleave",
                                        "mousemove",
                                        "mouseover",
                                        "mouseup",
                                        "search",
                                        "touchend",
                                        "touchstart",
                                    ].join(" "),
                                    function (t) {
                                        t.stopPropagation();
                                    }
                                );
                            }),
                            t
                        );
                    }),
                    (l = function (d) {
                        var p,
                            f,
                            t = ["wheel", "mousewheel", "DOMMouseScroll", "MozMousePixelScroll"],
                            e = "onwheel" in document || 9 <= document.documentMode ? ["wheel"] : ["mousewheel", "DomMouseScroll", "MozMousePixelScroll"],
                            g = Array.prototype.slice;
                        if (d.event.fixHooks) for (var i = t.length; i; ) d.event.fixHooks[t[--i]] = d.event.mouseHooks;
                        var m = (d.event.special.mousewheel = {
                            version: "3.1.12",
                            setup: function () {
                                if (this.addEventListener) for (var t = e.length; t; ) this.addEventListener(e[--t], n, !1);
                                else this.onmousewheel = n;
                                d.data(this, "mousewheel-line-height", m.getLineHeight(this)), d.data(this, "mousewheel-page-height", m.getPageHeight(this));
                            },
                            teardown: function () {
                                if (this.removeEventListener) for (var t = e.length; t; ) this.removeEventListener(e[--t], n, !1);
                                else this.onmousewheel = null;
                                d.removeData(this, "mousewheel-line-height"), d.removeData(this, "mousewheel-page-height");
                            },
                            getLineHeight: function (t) {
                                var e = d(t),
                                    i = e["offsetParent" in d.fn ? "offsetParent" : "parent"]();
                                return i.length || (i = d("body")), parseInt(i.css("fontSize"), 10) || parseInt(e.css("fontSize"), 10) || 16;
                            },
                            getPageHeight: function (t) {
                                return d(t).height();
                            },
                            settings: { adjustOldDeltas: !0, normalizeOffset: !0 },
                        });
                        function n(t) {
                            var e,
                                i = t || window.event,
                                n = g.call(arguments, 1),
                                s = 0,
                                o = 0,
                                r = 0,
                                a = 0,
                                l = 0;
                            if (
                                (((t = d.event.fix(i)).type = "mousewheel"),
                                "detail" in i && (r = -1 * i.detail),
                                "wheelDelta" in i && (r = i.wheelDelta),
                                "wheelDeltaY" in i && (r = i.wheelDeltaY),
                                "wheelDeltaX" in i && (o = -1 * i.wheelDeltaX),
                                "axis" in i && i.axis === i.HORIZONTAL_AXIS && ((o = -1 * r), (r = 0)),
                                (s = 0 === r ? o : r),
                                "deltaY" in i && (s = r = -1 * i.deltaY),
                                "deltaX" in i && ((o = i.deltaX), 0 === r && (s = -1 * o)),
                                0 !== r || 0 !== o)
                            ) {
                                if (1 === i.deltaMode) {
                                    var c = d.data(this, "mousewheel-line-height");
                                    (s *= c), (r *= c), (o *= c);
                                } else if (2 === i.deltaMode) {
                                    var h = d.data(this, "mousewheel-page-height");
                                    (s *= h), (r *= h), (o *= h);
                                }
                                if (
                                    ((e = Math.max(Math.abs(r), Math.abs(o))),
                                    (!f || e < f) && y(i, (f = e)) && (f /= 40),
                                    y(i, e) && ((s /= 40), (o /= 40), (r /= 40)),
                                    (s = Math[1 <= s ? "floor" : "ceil"](s / f)),
                                    (o = Math[1 <= o ? "floor" : "ceil"](o / f)),
                                    (r = Math[1 <= r ? "floor" : "ceil"](r / f)),
                                    m.settings.normalizeOffset && this.getBoundingClientRect)
                                ) {
                                    var u = this.getBoundingClientRect();
                                    (a = t.clientX - u.left), (l = t.clientY - u.top);
                                }
                                return (
                                    (t.deltaX = o),
                                    (t.deltaY = r),
                                    (t.deltaFactor = f),
                                    (t.offsetX = a),
                                    (t.offsetY = l),
                                    (t.deltaMode = 0),
                                    n.unshift(t, s, o, r),
                                    p && clearTimeout(p),
                                    (p = setTimeout(v, 200)),
                                    (d.event.dispatch || d.event.handle).apply(this, n)
                                );
                            }
                        }
                        function v() {
                            f = null;
                        }
                        function y(t, e) {
                            return m.settings.adjustOldDeltas && "mousewheel" === t.type && e % 120 === 0;
                        }
                        d.fn.extend({
                            mousewheel: function (t) {
                                return t ? this.bind("mousewheel", t) : this.trigger("mousewheel");
                            },
                            unmousewheel: function (t) {
                                return this.unbind("mousewheel", t);
                            },
                        });
                    }),
                    "function" === typeof t.define && t.define.amd ? t.define("jquery-mousewheel", ["jquery"], l) : "object" === typeof exports ? (module.exports = l) : l(u),
                    t.define("jquery.select2", ["jquery", "jquery-mousewheel", "./select2/core", "./select2/defaults", "./select2/utils"], function (s, t, o, e, r) {
                        if (null === s.fn.select2) {
                            var a = ["open", "close", "destroy"];
                            s.fn.select2 = function (e) {
                                if ("object" === typeof (e = e || {}))
                                    return (
                                        this.each(function () {
                                            var t = s.extend(!0, {}, e);
                                            new o(s(this), t);
                                        }),
                                        this
                                    );
                                if ("string" !==    typeof e) throw new Error("Invalid arguments for Select2: " + e);
                                var i,
                                    n = Array.prototype.slice.call(arguments, 1);
                                return (
                                    this.each(function () {
                                        var t = r.GetData(this, "select2");
                                        null === t && window.console && console.error && console.error("The select2('" + e + "') method was called on an element that is not using Select2."), (i = t[e].apply(t, n));
                                    }),
                                    -1 < s.inArray(e, a) ? this : i
                                );
                            };
                        }
                        return null === s.fn.select2.defaults && (s.fn.select2.defaults = e), o;
                    }),
                    { define: t.define, require: t.require }
                );
            })(),
            e = t.require("jquery.select2");
        return (u.fn.select2.amd = t), e;
    });
var Emitter = (function () {
        function t() {
            _classCallCheck(this, t);
        }
        return (
            _createClass(t, [
                {
                    key: "on",
                    value: function (t, e) {
                        return (this._callbacks = this._callbacks || {}), this._callbacks[t] || (this._callbacks[t] = []), this._callbacks[t].push(e), this;
                    },
                },
                {
                    key: "emit",
                    value: function (t) {
                        this._callbacks = this._callbacks || {};
                        var e = this._callbacks[t];
                        if (e) {
                            for (var i = arguments.length, n = new Array(1 < i ? i - 1 : 0), s = 1; s < i; s++) n[s - 1] = arguments[s];
                            var o = !0,
                                r = !1,
                                a = void 0;
                            try {
                                for (var l, c = e[Symbol.iterator](); !(o = (l = c.next()).done); o = !0) {
                                    l.value.apply(this, n);
                                }
                            } catch (t) {
                                (r = !0), (a = t);
                            } finally {
                                try {
                                    o || null === c.return || c.return();
                                } finally {
                                    if (r) throw a;
                                }
                            }
                        }
                        return this;
                    },
                },
                {
                    key: "off",
                    value: function (t, e) {
                        if (!this._callbacks || 0 === arguments.length) return (this._callbacks = {}), this;
                        var i = this._callbacks[t];
                        if (!i) return this;
                        if (1 === arguments.length) return delete this._callbacks[t], this;
                        for (var n = 0; n < i.length; n++) {
                            if (i[n] === e) {
                                i.splice(n, 1);
                                break;
                            }
                        }
                        return this;
                    },
                },
            ]),
            t
        );
    })(),
    Dropzone = (function () {
        function T(t, e) {
            var i, n, s;
            if (
                (_classCallCheck(this, T),
                ((i = _possibleConstructorReturn(this, _getPrototypeOf(T).call(this))).element = t),
                (i.version = T.version),
                (i.defaultOptions.previewTemplate = i.defaultOptions.previewTemplate.replace(/\n*/g, "")),
                (i.clickableElements = []),
                (i.listeners = []),
                (i.files = []),
                "string" === typeof i.element && (i.element = document.querySelector(i.element)),
                !i.element || null === i.element.nodeType)
            )
                throw new Error("Invalid dropzone element.");
            if (i.element.dropzone) throw new Error("Dropzone already attached.");
            T.instances.push(_assertThisInitialized(i)), (i.element.dropzone = _assertThisInitialized(i));
            var o = null !==    (s = T.optionsForElement(i.element)) ? s : {};
            if (((i.options = T.extend({}, i.defaultOptions, o, null !==    e ? e : {})), i.options.forceFallback || !T.isBrowserSupported())) return _possibleConstructorReturn(i, i.options.fallback.call(_assertThisInitialized(i)));
            if ((null === i.options.url && (i.options.url = i.element.getAttribute("action")), !i.options.url)) throw new Error("No URL provided.");
            if (i.options.acceptedFiles && i.options.acceptedMimeTypes) throw new Error("You can't provide both 'acceptedFiles' and 'acceptedMimeTypes'. 'acceptedMimeTypes' is deprecated.");
            if (i.options.uploadMultiple && i.options.chunking) throw new Error("You cannot set both: uploadMultiple and chunking.");
            return (
                i.options.acceptedMimeTypes && ((i.options.acceptedFiles = i.options.acceptedMimeTypes), delete i.options.acceptedMimeTypes),
                null !==    i.options.renameFilename &&
                    (i.options.renameFile = function (t) {
                        return i.options.renameFilename.call(_assertThisInitialized(i), t.name, t);
                    }),
                (i.options.method = i.options.method.toUpperCase()),
                (n = i.getExistingFallback()) && n.parentNode && n.parentNode.removeChild(n),
                !1 !== i.options.previewsContainer && (i.options.previewsContainer ? (i.previewsContainer = T.getElement(i.options.previewsContainer, "previewsContainer")) : (i.previewsContainer = i.element)),
                i.options.clickable && (!0 === i.options.clickable ? (i.clickableElements = [i.element]) : (i.clickableElements = T.getElements(i.options.clickable, "clickable"))),
                i.init(),
                i
            );
        }
        return (
            _inherits(T, Emitter),
            _createClass(T, null, [
                {
                    key: "initClass",
                    value: function () {
                        (this.prototype.Emitter = Emitter),
                            (this.prototype.events = [
                                "drop",
                                "dragstart",
                                "dragend",
                                "dragenter",
                                "dragover",
                                "dragleave",
                                "addedfile",
                                "addedfiles",
                                "removedfile",
                                "thumbnail",
                                "error",
                                "errormultiple",
                                "processing",
                                "processingmultiple",
                                "uploadprogress",
                                "totaluploadprogress",
                                "sending",
                                "sendingmultiple",
                                "success",
                                "successmultiple",
                                "canceled",
                                "canceledmultiple",
                                "complete",
                                "completemultiple",
                                "reset",
                                "maxfilesexceeded",
                                "maxfilesreached",
                                "queuecomplete",
                            ]),
                            (this.prototype.defaultOptions = {
                                url: null,
                                method: "post",
                                withCredentials: !1,
                                timeout: 3e4,
                                parallelUploads: 2,
                                uploadMultiple: !1,
                                chunking: !1,
                                forceChunking: !1,
                                chunkSize: 2e6,
                                parallelChunkUploads: !1,
                                retryChunks: !1,
                                retryChunksLimit: 3,
                                maxFilesize: 256,
                                paramName: "file",
                                createImageThumbnails: !0,
                                maxThumbnailFilesize: 10,
                                thumbnailWidth: 120,
                                thumbnailHeight: 120,
                                thumbnailMethod: "crop",
                                resizeWidth: null,
                                resizeHeight: null,
                                resizeMimeType: null,
                                resizeQuality: 0.8,
                                resizeMethod: "contain",
                                filesizeBase: 1e3,
                                maxFiles: null,
                                headers: null,
                                clickable: !0,
                                ignoreHiddenFiles: !0,
                                acceptedFiles: null,
                                acceptedMimeTypes: null,
                                autoProcessQueue: !0,
                                autoQueue: !0,
                                addRemoveLinks: !1,
                                previewsContainer: null,
                                hiddenInputContainer: "body",
                                capture: null,
                                renameFilename: null,
                                renameFile: null,
                                forceFallback: !1,
                                dictDefaultMessage: "Drop files here to upload",
                                dictFallbackMessage: "Your browser does not support drag'n'drop file uploads.",
                                dictFallbackText: "Please use the fallback form below to upload your files like in the olden days.",
                                dictFileTooBig: "File is too big ({{filesize}}MiB). Max filesize: {{maxFilesize}}MiB.",
                                dictInvalidFileType: "You can't upload files of this type.",
                                dictResponseError: "Server responded with {{statusCode}} code.",
                                dictCancelUpload: "Cancel upload",
                                dictUploadCanceled: "Upload canceled.",
                                dictCancelUploadConfirmation: "Are you sure you want to cancel this upload?",
                                dictRemoveFile: "Remove file",
                                dictRemoveFileConfirmation: null,
                                dictMaxFilesExceeded: "You can not upload any more files.",
                                dictFileSizeUnits: { tb: "TB", gb: "GB", mb: "MB", kb: "KB", b: "b" },
                                init: function () {},
                                params: function (t, e, i) {
                                    if (i)
                                        return {
                                            dzuuid: i.file.upload.uuid,
                                            dzchunkindex: i.index,
                                            dztotalfilesize: i.file.size,
                                            dzchunksize: this.options.chunkSize,
                                            dztotalchunkcount: i.file.upload.totalChunkCount,
                                            dzchunkbyteoffset: i.index * this.options.chunkSize,
                                        };
                                },
                                accept: function (t, e) {
                                    return e();
                                },
                                chunksUploaded: function (t, e) {
                                    e();
                                },
                                fallback: function () {
                                    var t;
                                    this.element.className = "".concat(this.element.className, " dz-browser-not-supported");
                                    var e = !0,
                                        i = !1,
                                        n = void 0;
                                    try {
                                        for (var s, o = this.element.getElementsByTagName("div")[Symbol.iterator](); !(e = (s = o.next()).done); e = !0) {
                                            var r = s.value;
                                            if (/(^| )dz-message($| )/.test(r.className)) {
                                                (t = r).className = "dz-message";
                                                break;
                                            }
                                        }
                                    } catch (t) {
                                        (i = !0), (n = t);
                                    } finally {
                                        try {
                                            e || null === o.return || o.return();
                                        } finally {
                                            if (i) throw n;
                                        }
                                    }
                                    t || ((t = T.createElement('<div class="dz-message"><span></span></div>')), this.element.appendChild(t));
                                    var a = t.getElementsByTagName("span")[0];
                                    return (
                                        a && (null !==    a.textContent ? (a.textContent = this.options.dictFallbackMessage) : null !==    a.innerText && (a.innerText = this.options.dictFallbackMessage)),
                                        this.element.appendChild(this.getFallbackForm())
                                    );
                                },
                                resize: function (t, e, i, n) {
                                    var s = { srcX: 0, srcY: 0, srcWidth: t.width, srcHeight: t.height },
                                        o = t.width / t.height;
                                    null === e && null === i ? ((e = s.srcWidth), (i = s.srcHeight)) : null === e ? (e = i * o) : null === i && (i = e / o);
                                    var r = (e = Math.min(e, s.srcWidth)) / (i = Math.min(i, s.srcHeight));
                                    if (s.srcWidth > e || s.srcHeight > i)
                                        if ("crop" === n) r < o ? ((s.srcHeight = t.height), (s.srcWidth = s.srcHeight * r)) : ((s.srcWidth = t.width), (s.srcHeight = s.srcWidth / r));
                                        else {
                                            if ("contain" !== n) throw new Error("Unknown resizeMethod '".concat(n, "'"));
                                            r < o ? (i = e / o) : (e = i * o);
                                        }
                                    return (s.srcX = (t.width - s.srcWidth) / 2), (s.srcY = (t.height - s.srcHeight) / 2), (s.trgWidth = e), (s.trgHeight = i), s;
                                },
                                transformFile: function (t, e) {
                                    return (this.options.resizeWidth || this.options.resizeHeight) && t.type.match(/image.*/) ? this.resizeImage(t, this.options.resizeWidth, this.options.resizeHeight, this.options.resizeMethod, e) : e(t);
                                },
                                previewTemplate:
                                    '<div class="dz-preview dz-file-preview">\n  <div class="dz-image"><img data-dz-thumbnail /></div>\n  <div class="dz-details">\n    <div class="dz-size"><span data-dz-size></span></div>\n    <div class="dz-filename"><span data-dz-name></span></div>\n  </div>\n  <div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div>\n  <div class="dz-error-message"><span data-dz-errormessage></span></div>\n  <div class="dz-success-mark">\n    <svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">\n      <title>Check</title>\n      <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">\n        <path d="M23.5,31.8431458 L17.5852419,25.9283877 C16.0248253,24.3679711 13.4910294,24.366835 11.9289322,25.9289322 C10.3700136,27.4878508 10.3665912,30.0234455 11.9283877,31.5852419 L20.4147581,40.0716123 C20.5133999,40.1702541 20.6159315,40.2626649 20.7218615,40.3488435 C22.2835669,41.8725651 24.794234,41.8626202 26.3461564,40.3106978 L43.3106978,23.3461564 C44.8771021,21.7797521 44.8758057,19.2483887 43.3137085,17.6862915 C41.7547899,16.1273729 39.2176035,16.1255422 37.6538436,17.6893022 L23.5,31.8431458 Z M27,53 C41.3594035,53 53,41.3594035 53,27 C53,12.6405965 41.3594035,1 27,1 C12.6405965,1 1,12.6405965 1,27 C1,41.3594035 12.6405965,53 27,53 Z" stroke-opacity="0.198794158" stroke="#747474" fill-opacity="0.816519475" fill="#FFFFFF"></path>\n      </g>\n    </svg>\n  </div>\n  <div class="dz-error-mark">\n    <svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">\n      <title>Error</title>\n      <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">\n        <g stroke="#747474" stroke-opacity="0.198794158" fill="#FFFFFF" fill-opacity="0.816519475">\n          <path d="M32.6568542,29 L38.3106978,23.3461564 C39.8771021,21.7797521 39.8758057,19.2483887 38.3137085,17.6862915 C36.7547899,16.1273729 34.2176035,16.1255422 32.6538436,17.6893022 L27,23.3431458 L21.3461564,17.6893022 C19.7823965,16.1255422 17.2452101,16.1273729 15.6862915,17.6862915 C14.1241943,19.2483887 14.1228979,21.7797521 15.6893022,23.3461564 L21.3431458,29 L15.6893022,34.6538436 C14.1228979,36.2202479 14.1241943,38.7516113 15.6862915,40.3137085 C17.2452101,41.8726271 19.7823965,41.8744578 21.3461564,40.3106978 L27,34.6568542 L32.6538436,40.3106978 C34.2176035,41.8744578 36.7547899,41.8726271 38.3137085,40.3137085 C39.8758057,38.7516113 39.8771021,36.2202479 38.3106978,34.6538436 L32.6568542,29 Z M27,53 C41.3594035,53 53,41.3594035 53,27 C53,12.6405965 41.3594035,1 27,1 C12.6405965,1 1,12.6405965 1,27 C1,41.3594035 12.6405965,53 27,53 Z"></path>\n        </g>\n      </g>\n    </svg>\n  </div>\n</div>',
                                drop: function () {
                                    return this.element.classList.remove("dz-drag-hover");
                                },
                                dragstart: function () {},
                                dragend: function () {
                                    return this.element.classList.remove("dz-drag-hover");
                                },
                                dragenter: function () {
                                    return this.element.classList.add("dz-drag-hover");
                                },
                                dragover: function () {
                                    return this.element.classList.add("dz-drag-hover");
                                },
                                dragleave: function () {
                                    return this.element.classList.remove("dz-drag-hover");
                                },
                                paste: function () {},
                                reset: function () {
                                    return this.element.classList.remove("dz-started");
                                },
                                addedfile: function (e) {
                                    var i = this;
                                    if ((this.element === this.previewsContainer && this.element.classList.add("dz-started"), this.previewsContainer)) {
                                        (e.previewElement = T.createElement(this.options.previewTemplate.trim())), (e.previewTemplate = e.previewElement), this.previewsContainer.appendChild(e.previewElement);
                                        var t = !0,
                                            n = !1,
                                            s = void 0;
                                        try {
                                            for (var o, r = e.previewElement.querySelectorAll("[data-dz-name]")[Symbol.iterator](); !(t = (o = r.next()).done); t = !0) {
                                                var a = o.value;
                                                a.textContent = e.name;
                                            }
                                        } catch (t) {
                                            (n = !0), (s = t);
                                        } finally {
                                            try {
                                                t || null === r.return || r.return();
                                            } finally {
                                                if (n) throw s;
                                            }
                                        }
                                        var l = !0,
                                            c = !1,
                                            h = void 0;
                                        try {
                                            for (var u, d = e.previewElement.querySelectorAll("[data-dz-size]")[Symbol.iterator](); !(l = (u = d.next()).done); l = !0) (a = u.value).innerHTML = this.filesize(e.size);
                                        } catch (t) {
                                            (c = !0), (h = t);
                                        } finally {
                                            try {
                                                l || null === d.return || d.return();
                                            } finally {
                                                if (c) throw h;
                                            }
                                        }
                                        this.options.addRemoveLinks &&
                                            ((e._removeLink = T.createElement('<a class="dz-remove" href="javascript:undefined;" data-dz-remove>'.concat(this.options.dictRemoveFile, "</a>"))), e.previewElement.appendChild(e._removeLink));
                                        function p(t) {
                                            return (
                                                t.preventDefault(),
                                                t.stopPropagation(),
                                                e.status === T.UPLOADING
                                                    ? T.confirm(i.options.dictCancelUploadConfirmation, function () {
                                                          return i.removeFile(e);
                                                      })
                                                    : i.options.dictRemoveFileConfirmation
                                                    ? T.confirm(i.options.dictRemoveFileConfirmation, function () {
                                                          return i.removeFile(e);
                                                      })
                                                    : i.removeFile(e)
                                            );
                                        }
                                        var f = !0,
                                            g = !1,
                                            m = void 0;
                                        try {
                                            for (var v, y = e.previewElement.querySelectorAll("[data-dz-remove]")[Symbol.iterator](); !(f = (v = y.next()).done); f = !0) {
                                                v.value.addEventListener("click", p);
                                            }
                                        } catch (t) {
                                            (g = !0), (m = t);
                                        } finally {
                                            try {
                                                f || null === y.return || y.return();
                                            } finally {
                                                if (g) throw m;
                                            }
                                        }
                                    }
                                },
                                removedfile: function (t) {
                                    return null !==    t.previewElement && null !==    t.previewElement.parentNode && t.previewElement.parentNode.removeChild(t.previewElement), this._updateMaxFilesReachedClass();
                                },
                                thumbnail: function (t, e) {
                                    if (t.previewElement) {
                                        t.previewElement.classList.remove("dz-file-preview");
                                        var i = !0,
                                            n = !1,
                                            s = void 0;
                                        try {
                                            for (var o, r = t.previewElement.querySelectorAll("[data-dz-thumbnail]")[Symbol.iterator](); !(i = (o = r.next()).done); i = !0) {
                                                var a = o.value;
                                                (a.alt = t.name), (a.src = e);
                                            }
                                        } catch (t) {
                                            (n = !0), (s = t);
                                        } finally {
                                            try {
                                                i || null === r.return || r.return();
                                            } finally {
                                                if (n) throw s;
                                            }
                                        }
                                        return setTimeout(function () {
                                            return t.previewElement.classList.add("dz-image-preview");
                                        }, 1);
                                    }
                                },
                                error: function (t, e) {
                                    if (t.previewElement) {
                                        t.previewElement.classList.add("dz-error"), "String" !==    typeof e && e.error && (e = e.error);
                                        var i = !0,
                                            n = !1,
                                            s = void 0;
                                        try {
                                            for (var o, r = t.previewElement.querySelectorAll("[data-dz-errormessage]")[Symbol.iterator](); !(i = (o = r.next()).done); i = !0) {
                                                o.value.textContent = e;
                                            }
                                        } catch (t) {
                                            (n = !0), (s = t);
                                        } finally {
                                            try {
                                                i || null === r.return || r.return();
                                            } finally {
                                                if (n) throw s;
                                            }
                                        }
                                    }
                                },
                                errormultiple: function () {},
                                processing: function (t) {
                                    if (t.previewElement && (t.previewElement.classList.add("dz-processing"), t._removeLink)) return (t._removeLink.innerHTML = this.options.dictCancelUpload);
                                },
                                processingmultiple: function () {},
                                uploadprogress: function (t, e) {
                                    if (t.previewElement) {
                                        var i = !0,
                                            n = !1,
                                            s = void 0;
                                        try {
                                            for (var o, r = t.previewElement.querySelectorAll("[data-dz-uploadprogress]")[Symbol.iterator](); !(i = (o = r.next()).done); i = !0) {
                                                var a = o.value;
                                                "PROGRESS" === a.nodeName ? (a.value = e) : (a.style.width = "".concat(e, "%"));
                                            }
                                        } catch (t) {
                                            (n = !0), (s = t);
                                        } finally {
                                            try {
                                                i || null === r.return || r.return();
                                            } finally {
                                                if (n) throw s;
                                            }
                                        }
                                    }
                                },
                                totaluploadprogress: function () {},
                                sending: function () {},
                                sendingmultiple: function () {},
                                success: function (t) {
                                    if (t.previewElement) return t.previewElement.classList.add("dz-success");
                                },
                                successmultiple: function () {},
                                canceled: function (t) {
                                    return this.emit("error", t, this.options.dictUploadCanceled);
                                },
                                canceledmultiple: function () {},
                                complete: function (t) {
                                    if ((t._removeLink && (t._removeLink.innerHTML = this.options.dictRemoveFile), t.previewElement)) return t.previewElement.classList.add("dz-complete");
                                },
                                completemultiple: function () {},
                                maxfilesexceeded: function () {},
                                maxfilesreached: function () {},
                                queuecomplete: function () {},
                                addedfiles: function () {},
                            }),
                            (this.prototype._thumbnailQueue = []),
                            (this.prototype._processingThumbnail = !1);
                    },
                },
                {
                    key: "extend",
                    value: function (t) {
                        for (var e = arguments.length, i = new Array(1 < e ? e - 1 : 0), n = 1; n < e; n++) i[n - 1] = arguments[n];
                        for (var s = 0, o = i; s < o.length; s++) {
                            var r = o[s];
                            for (var a in r) {
                                var l = r[a];
                                t[a] = l;
                            }
                        }
                        return t;
                    },
                },
            ]),
            _createClass(
                T,
                [
                    {
                        key: "getAcceptedFiles",
                        value: function () {
                            return this.files
                                .filter(function (t) {
                                    return t.accepted;
                                })
                                .map(function (t) {
                                    return t;
                                });
                        },
                    },
                    {
                        key: "getRejectedFiles",
                        value: function () {
                            return this.files
                                .filter(function (t) {
                                    return !t.accepted;
                                })
                                .map(function (t) {
                                    return t;
                                });
                        },
                    },
                    {
                        key: "getFilesWithStatus",
                        value: function (e) {
                            return this.files
                                .filter(function (t) {
                                    return t.status === e;
                                })
                                .map(function (t) {
                                    return t;
                                });
                        },
                    },
                    {
                        key: "getQueuedFiles",
                        value: function () {
                            return this.getFilesWithStatus(T.QUEUED);
                        },
                    },
                    {
                        key: "getUploadingFiles",
                        value: function () {
                            return this.getFilesWithStatus(T.UPLOADING);
                        },
                    },
                    {
                        key: "getAddedFiles",
                        value: function () {
                            return this.getFilesWithStatus(T.ADDED);
                        },
                    },
                    {
                        key: "getActiveFiles",
                        value: function () {
                            return this.files
                                .filter(function (t) {
                                    return t.status === T.UPLOADING || t.status === T.QUEUED;
                                })
                                .map(function (t) {
                                    return t;
                                });
                        },
                    },
                    {
                        key: "init",
                        value: function () {
                            var l = this;
                            if (
                                ("form" === this.element.tagName && this.element.setAttribute("enctype", "multipart/form-data"),
                                this.element.classList.contains("dropzone") &&
                                    !this.element.querySelector(".dz-message") &&
                                    this.element.appendChild(T.createElement('<div class="dz-default dz-message"><button class="dz-button" type="button">'.concat(this.options.dictDefaultMessage, "</button></div>"))),
                                this.clickableElements.length)
                            ) {
                                !(function a() {
                                    return (
                                        l.hiddenFileInput && l.hiddenFileInput.parentNode.removeChild(l.hiddenFileInput),
                                        (l.hiddenFileInput = document.createElement("input")),
                                        l.hiddenFileInput.setAttribute("type", "file"),
                                        (null === l.options.maxFiles || 1 < l.options.maxFiles) && l.hiddenFileInput.setAttribute("multiple", "multiple"),
                                        (l.hiddenFileInput.className = "dz-hidden-input"),
                                        null !== l.options.acceptedFiles && l.hiddenFileInput.setAttribute("accept", l.options.acceptedFiles),
                                        null !== l.options.capture && l.hiddenFileInput.setAttribute("capture", l.options.capture),
                                        (l.hiddenFileInput.style.visibility = "hidden"),
                                        (l.hiddenFileInput.style.position = "absolute"),
                                        (l.hiddenFileInput.style.top = "0"),
                                        (l.hiddenFileInput.style.left = "0"),
                                        (l.hiddenFileInput.style.height = "0"),
                                        (l.hiddenFileInput.style.width = "0"),
                                        T.getElement(l.options.hiddenInputContainer, "hiddenInputContainer").appendChild(l.hiddenFileInput),
                                        l.hiddenFileInput.addEventListener("change", function () {
                                            var t = l.hiddenFileInput.files;
                                            if (t.length) {
                                                var e = !0,
                                                    i = !1,
                                                    n = void 0;
                                                try {
                                                    for (var s, o = t[Symbol.iterator](); !(e = (s = o.next()).done); e = !0) {
                                                        var r = s.value;
                                                        l.addFile(r);
                                                    }
                                                } catch (t) {
                                                    (i = !0), (n = t);
                                                } finally {
                                                    try {
                                                        e || null === o.return || o.return();
                                                    } finally {
                                                        if (i) throw n;
                                                    }
                                                }
                                            }
                                            return l.emit("addedfiles", t), a();
                                        })
                                    );
                                })();
                            }
                            this.URL = null !== window.URL ? window.URL : window.webkitURL;
                            var t = !0,
                                e = !1,
                                i = void 0;
                            try {
                                for (var n, s = this.events[Symbol.iterator](); !(t = (n = s.next()).done); t = !0) {
                                    var o = n.value;
                                    this.on(o, this.options[o]);
                                }
                            } catch (t) {
                                (e = !0), (i = t);
                            } finally {
                                try {
                                    t || null === s.return || s.return();
                                } finally {
                                    if (e) throw i;
                                }
                            }
                            this.on("uploadprogress", function () {
                                return l.updateTotalUploadProgress();
                            }),
                                this.on("removedfile", function () {
                                    return l.updateTotalUploadProgress();
                                }),
                                this.on("canceled", function (t) {
                                    return l.emit("complete", t);
                                }),
                                this.on("complete", function (t) {
                                    if (0 === l.getAddedFiles().length && 0 === l.getUploadingFiles().length && 0 === l.getQueuedFiles().length)
                                        return setTimeout(function () {
                                            return l.emit("queuecomplete");
                                        }, 0);
                                });
                            function r(t) {
                                var e;
                                return (
                                    (e = t).dataTransfer.types &&
                                    e.dataTransfer.types.some(function (t) {
                                        return "Files" === t;
                                    }) &&
                                    (t.stopPropagation(), t.preventDefault ? t.preventDefault() : (t.returnValue = !1))
                                );
                            }
                            return (
                                (this.listeners = [
                                    {
                                        element: this.element,
                                        events: {
                                            dragstart: function (t) {
                                                return l.emit("dragstart", t);
                                            },
                                            dragenter: function (t) {
                                                return r(t), l.emit("dragenter", t);
                                            },
                                            dragover: function (t) {
                                                var e;
                                                try {
                                                    e = t.dataTransfer.effectAllowed;
                                                } catch (t) {}
                                                return (t.dataTransfer.dropEffect = "move" === e || "linkMove" === e ? "move" : "copy"), r(t), l.emit("dragover", t);
                                            },
                                            dragleave: function (t) {
                                                return l.emit("dragleave", t);
                                            },
                                            drop: function (t) {
                                                return r(t), l.drop(t);
                                            },
                                            dragend: function (t) {
                                                return l.emit("dragend", t);
                                            },
                                        },
                                    },
                                ]),
                                this.clickableElements.forEach(function (e) {
                                    return l.listeners.push({
                                        element: e,
                                        events: {
                                            click: function (t) {
                                                return (e === l.element && t.target !== l.element && !T.elementInside(t.target, l.element.querySelector(".dz-message"))) || l.hiddenFileInput.click(), !0;
                                            },
                                        },
                                    });
                                }),
                                this.enable(),
                                this.options.init.call(this)
                            );
                        },
                    },
                    {
                        key: "destroy",
                        value: function () {
                            return (
                                this.disable(),
                                this.removeAllFiles(!0),
                                null !==    this.hiddenFileInput && this.hiddenFileInput.parentNode && (this.hiddenFileInput.parentNode.removeChild(this.hiddenFileInput), (this.hiddenFileInput = null)),
                                delete this.element.dropzone,
                                T.instances.splice(T.instances.indexOf(this), 1)
                            );
                        },
                    },
                    {
                        key: "updateTotalUploadProgress",
                        value: function () {
                            var t,
                                e = 0,
                                i = 0;
                            if (this.getActiveFiles().length) {
                                var n = !0,
                                    s = !1,
                                    o = void 0;
                                try {
                                    for (var r, a = this.getActiveFiles()[Symbol.iterator](); !(n = (r = a.next()).done); n = !0) {
                                        var l = r.value;
                                        (e += l.upload.bytesSent), (i += l.upload.total);
                                    }
                                } catch (t) {
                                    (s = !0), (o = t);
                                } finally {
                                    try {
                                        n || null === a.return || a.return();
                                    } finally {
                                        if (s) throw o;
                                    }
                                }
                                t = (100 * e) / i;
                            } else t = 100;
                            return this.emit("totaluploadprogress", t, i, e);
                        },
                    },
                    {
                        key: "_getParamName",
                        value: function (t) {
                            return "function" === typeof this.options.paramName ? this.options.paramName(t) : "".concat(this.options.paramName).concat(this.options.uploadMultiple ? "[".concat(t, "]") : "");
                        },
                    },
                    {
                        key: "_renameFile",
                        value: function (t) {
                            return "function" !==    typeof this.options.renameFile ? t.name : this.options.renameFile(t);
                        },
                    },
                    {
                        key: "getFallbackForm",
                        value: function () {
                            var t, e;
                            if ((t = this.getExistingFallback())) return t;
                            var i = '<div class="dz-fallback">';
                            this.options.dictFallbackText && (i += "<p>".concat(this.options.dictFallbackText, "</p>")),
                                (i += '<input type="file" name="'.concat(this._getParamName(0), '" ').concat(this.options.uploadMultiple ? 'multiple="multiple"' : void 0, ' /><input type="submit" value="Upload!"></div>'));
                            var n = T.createElement(i);
                            return (
                                "FORM" !== this.element.tagName
                                    ? (e = T.createElement('<form action="'.concat(this.options.url, '" enctype="multipart/form-data" method="').concat(this.options.method, '"></form>'))).appendChild(n)
                                    : (this.element.setAttribute("enctype", "multipart/form-data"), this.element.setAttribute("method", this.options.method)),
                                null !==    e ? e : n
                            );
                        },
                    },
                    {
                        key: "getExistingFallback",
                        value: function () {
                            function t(t) {
                                var e = !0,
                                    i = !1,
                                    n = void 0;
                                try {
                                    for (var s, o = t[Symbol.iterator](); !(e = (s = o.next()).done); e = !0) {
                                        var r = s.value;
                                        if (/(^| )fallback($| )/.test(r.className)) return r;
                                    }
                                } catch (t) {
                                    (i = !0), (n = t);
                                } finally {
                                    try {
                                        e || null === o.return || o.return();
                                    } finally {
                                        if (i) throw n;
                                    }
                                }
                            }
                            for (var e = 0, i = ["div", "form"]; e < i.length; e++) {
                                var n,
                                    s = i[e];
                                if ((n = t(this.element.getElementsByTagName(s)))) return n;
                            }
                        },
                    },
                    {
                        key: "setupEventListeners",
                        value: function () {
                            return this.listeners.map(function (n) {
                                return (function () {
                                    var t = [];
                                    for (var e in n.events) {
                                        var i = n.events[e];
                                        t.push(n.element.addEventListener(e, i, !1));
                                    }
                                    return t;
                                })();
                            });
                        },
                    },
                    {
                        key: "removeEventListeners",
                        value: function () {
                            return this.listeners.map(function (n) {
                                return (function () {
                                    var t = [];
                                    for (var e in n.events) {
                                        var i = n.events[e];
                                        t.push(n.element.removeEventListener(e, i, !1));
                                    }
                                    return t;
                                })();
                            });
                        },
                    },
                    {
                        key: "disable",
                        value: function () {
                            var e = this;
                            return (
                                this.clickableElements.forEach(function (t) {
                                    return t.classList.remove("dz-clickable");
                                }),
                                this.removeEventListeners(),
                                (this.disabled = !0),
                                this.files.map(function (t) {
                                    return e.cancelUpload(t);
                                })
                            );
                        },
                    },
                    {
                        key: "enable",
                        value: function () {
                            return (
                                delete this.disabled,
                                this.clickableElements.forEach(function (t) {
                                    return t.classList.add("dz-clickable");
                                }),
                                this.setupEventListeners()
                            );
                        },
                    },
                    {
                        key: "filesize",
                        value: function (t) {
                            var e = 0,
                                i = "b";
                            if (0 < t) {
                                for (var n = ["tb", "gb", "mb", "kb", "b"], s = 0; s < n.length; s++) {
                                    var o = n[s];
                                    if (Math.pow(this.options.filesizeBase, 4 - s) / 10 <= t) {
                                        (e = t / Math.pow(this.options.filesizeBase, 4 - s)), (i = o);
                                        break;
                                    }
                                }
                                e = Math.round(10 * e) / 10;
                            }
                            return "<strong>".concat(e, "</strong> ").concat(this.options.dictFileSizeUnits[i]);
                        },
                    },
                    {
                        key: "_updateMaxFilesReachedClass",
                        value: function () {
                            return null !==    this.options.maxFiles && this.getAcceptedFiles().length >= this.options.maxFiles
                                ? (this.getAcceptedFiles().length === this.options.maxFiles && this.emit("maxfilesreached", this.files), this.element.classList.add("dz-max-files-reached"))
                                : this.element.classList.remove("dz-max-files-reached");
                        },
                    },
                    {
                        key: "drop",
                        value: function (t) {
                            if (t.dataTransfer) {
                                this.emit("drop", t);
                                for (var e = [], i = 0; i < t.dataTransfer.files.length; i++) e[i] = t.dataTransfer.files[i];
                                if (e.length) {
                                    var n = t.dataTransfer.items;
                                    n && n.length && null !==    n[0].webkitGetAsEntry ? this._addFilesFromItems(n) : this.handleFiles(e);
                                }
                                this.emit("addedfiles", e);
                            }
                        },
                    },
                    {
                        key: "paste",
                        value: function (t) {
                            if (
                                null !=
                                __guard__(null !==    t ? t.clipboardData : void 0, function (t) {
                                    return t.items;
                                })
                            ) {
                                this.emit("paste", t);
                                var e = t.clipboardData.items;
                                return e.length ? this._addFilesFromItems(e) : void 0;
                            }
                        },
                    },
                    {
                        key: "handleFiles",
                        value: function (t) {
                            var e = !0,
                                i = !1,
                                n = void 0;
                            try {
                                for (var s, o = t[Symbol.iterator](); !(e = (s = o.next()).done); e = !0) {
                                    var r = s.value;
                                    this.addFile(r);
                                }
                            } catch (t) {
                                (i = !0), (n = t);
                            } finally {
                                try {
                                    e || null === o.return || o.return();
                                } finally {
                                    if (i) throw n;
                                }
                            }
                        },
                    },
                    {
                        key: "_addFilesFromItems",
                        value: function (l) {
                            var c = this;
                            return (function () {
                                var t = [],
                                    e = !0,
                                    i = !1,
                                    n = void 0;
                                try {
                                    for (var s, o = l[Symbol.iterator](); !(e = (s = o.next()).done); e = !0) {
                                        var r,
                                            a = s.value;
                                        null !==    a.webkitGetAsEntry && (r = a.webkitGetAsEntry())
                                            ? r.isFile
                                                ? t.push(c.addFile(a.getAsFile()))
                                                : r.isDirectory
                                                ? t.push(c._addFilesFromDirectory(r, r.name))
                                                : t.push(void 0)
                                            : null !==    a.getAsFile && (null === a.kind || "file" === a.kind)
                                            ? t.push(c.addFile(a.getAsFile()))
                                            : t.push(void 0);
                                    }
                                } catch (t) {
                                    (i = !0), (n = t);
                                } finally {
                                    try {
                                        e || null === o.return || o.return();
                                    } finally {
                                        if (i) throw n;
                                    }
                                }
                                return t;
                            })();
                        },
                    },
                    {
                        key: "_addFilesFromDirectory",
                        value: function (t, l) {
                            function e(e) {
                                return __guardMethod__(console, "log", function (t) {
                                    return t.log(e);
                                });
                            }
                            var c = this,
                                i = t.createReader();
                            return (function a() {
                                return i.readEntries(function (t) {
                                    if (0 < t.length) {
                                        var e = !0,
                                            i = !1,
                                            n = void 0;
                                        try {
                                            for (var s, o = t[Symbol.iterator](); !(e = (s = o.next()).done); e = !0) {
                                                var r = s.value;
                                                r.isFile
                                                    ? r.file(function (t) {
                                                          if (!c.options.ignoreHiddenFiles || "." !== t.name.substring(0, 1)) return (t.fullPath = "".concat(l, "/").concat(t.name)), c.addFile(t);
                                                      })
                                                    : r.isDirectory && c._addFilesFromDirectory(r, "".concat(l, "/").concat(r.name));
                                            }
                                        } catch (t) {
                                            (i = !0), (n = t);
                                        } finally {
                                            try {
                                                e || null === o.return || o.return();
                                            } finally {
                                                if (i) throw n;
                                            }
                                        }
                                        a();
                                    }
                                    return null;
                                }, e);
                            })();
                        },
                    },
                    {
                        key: "accept",
                        value: function (t, e) {
                            this.options.maxFilesize && t.size > 1024 * this.options.maxFilesize * 1024
                                ? e(this.options.dictFileTooBig.replace("{{filesize}}", Math.round(t.size / 1024 / 10.24) / 100).replace("{{maxFilesize}}", this.options.maxFilesize))
                                : T.isValidFile(t, this.options.acceptedFiles)
                                ? null !==    this.options.maxFiles && this.getAcceptedFiles().length >= this.options.maxFiles
                                    ? (e(this.options.dictMaxFilesExceeded.replace("{{maxFiles}}", this.options.maxFiles)), this.emit("maxfilesexceeded", t))
                                    : this.options.accept.call(this, t, e)
                                : e(this.options.dictInvalidFileType);
                        },
                    },
                    {
                        key: "addFile",
                        value: function (e) {
                            var i = this;
                            (e.upload = { uuid: T.uuidv4(), progress: 0, total: e.size, bytesSent: 0, filename: this._renameFile(e) }),
                                this.files.push(e),
                                (e.status = T.ADDED),
                                this.emit("addedfile", e),
                                this._enqueueThumbnail(e),
                                this.accept(e, function (t) {
                                    t ? ((e.accepted = !1), i._errorProcessing([e], t)) : ((e.accepted = !0), i.options.autoQueue && i.enqueueFile(e)), i._updateMaxFilesReachedClass();
                                });
                        },
                    },
                    {
                        key: "enqueueFiles",
                        value: function (t) {
                            var e = !0,
                                i = !1,
                                n = void 0;
                            try {
                                for (var s, o = t[Symbol.iterator](); !(e = (s = o.next()).done); e = !0) {
                                    var r = s.value;
                                    this.enqueueFile(r);
                                }
                            } catch (t) {
                                (i = !0), (n = t);
                            } finally {
                                try {
                                    e || null === o.return || o.return();
                                } finally {
                                    if (i) throw n;
                                }
                            }
                            return null;
                        },
                    },
                    {
                        key: "enqueueFile",
                        value: function (t) {
                            var e = this;
                            if (t.status !== T.ADDED || !0 !== t.accepted) throw new Error("This file can't be queued because it has already been processed or was rejected.");
                            if (((t.status = T.QUEUED), this.options.autoProcessQueue))
                                return setTimeout(function () {
                                    return e.processQueue();
                                }, 0);
                        },
                    },
                    {
                        key: "_enqueueThumbnail",
                        value: function (t) {
                            var e = this;
                            if (this.options.createImageThumbnails && t.type.match(/image.*/) && t.size <= 1024 * this.options.maxThumbnailFilesize * 1024)
                                return (
                                    this._thumbnailQueue.push(t),
                                    setTimeout(function () {
                                        return e._processThumbnailQueue();
                                    }, 0)
                                );
                        },
                    },
                    {
                        key: "_processThumbnailQueue",
                        value: function () {
                            var e = this;
                            if (!this._processingThumbnail && 0 !== this._thumbnailQueue.length) {
                                this._processingThumbnail = !0;
                                var i = this._thumbnailQueue.shift();
                                return this.createThumbnail(i, this.options.thumbnailWidth, this.options.thumbnailHeight, this.options.thumbnailMethod, !0, function (t) {
                                    return e.emit("thumbnail", i, t), (e._processingThumbnail = !1), e._processThumbnailQueue();
                                });
                            }
                        },
                    },
                    {
                        key: "removeFile",
                        value: function (t) {
                            if ((t.status === T.UPLOADING && this.cancelUpload(t), (this.files = without(this.files, t)), this.emit("removedfile", t), 0 === this.files.length)) return this.emit("reset");
                        },
                    },
                    {
                        key: "removeAllFiles",
                        value: function (t) {
                            null === t && (t = !1);
                            var e = !0,
                                i = !1,
                                n = void 0;
                            try {
                                for (var s, o = this.files.slice()[Symbol.iterator](); !(e = (s = o.next()).done); e = !0) {
                                    var r = s.value;
                                    (r.status === T.UPLOADING && !t) || this.removeFile(r);
                                }
                            } catch (t) {
                                (i = !0), (n = t);
                            } finally {
                                try {
                                    e || null === o.return || o.return();
                                } finally {
                                    if (i) throw n;
                                }
                            }
                            return null;
                        },
                    },
                    {
                        key: "resizeImage",
                        value: function (s, t, e, i, o) {
                            var r = this;
                            return this.createThumbnail(s, t, e, i, !0, function (t, e) {
                                if (null === e) return o(s);
                                var i = r.options.resizeMimeType;
                                null === i && (i = s.type);
                                var n = e.toDataURL(i, r.options.resizeQuality);
                                return ("image/jpeg" !== i && "image/jpg" !== i) || (n = ExifRestore.restore(s.dataURL, n)), o(T.dataURItoBlob(n));
                            });
                        },
                    },
                    {
                        key: "createThumbnail",
                        value: function (t, e, i, n, s, o) {
                            var r = this,
                                a = new FileReader();
                            (a.onload = function () {
                                (t.dataURL = a.result), "image/svg+xml" !== t.type ? r.createThumbnailFromUrl(t, e, i, n, s, o) : null !==    o && o(a.result);
                            }),
                                a.readAsDataURL(t);
                        },
                    },
                    {
                        key: "displayExistingFile",
                        value: function (e, t, i, n, s) {
                            var o = this,
                                r = !(4 < arguments.length && void 0 !== s) || s;
                            if ((this.emit("addedfile", e), this.emit("complete", e), r)) {
                                (e.dataURL = t),
                                    this.createThumbnailFromUrl(
                                        e,
                                        this.options.thumbnailWidth,
                                        this.options.thumbnailHeight,
                                        this.options.resizeMethod,
                                        this.options.fixOrientation,
                                        function (t) {
                                            o.emit("thumbnail", e, t), i && i();
                                        },
                                        n
                                    );
                            } else this.emit("thumbnail", e, t), i && i();
                        },
                    },
                    {
                        key: "createThumbnailFromUrl",
                        value: function (o, r, a, l, e, c, t) {
                            var h = this,
                                u = document.createElement("img");
                            return (
                                t && (u.crossOrigin = t),
                                (u.onload = function () {
                                    var t = function (t) {
                                        return t(1);
                                    };
                                    return (
                                        "undefined" !==    typeof EXIF &&
                                            null !== EXIF &&
                                            e &&
                                            (t = function (t) {
                                                return EXIF.getData(u, function () {
                                                    return t(EXIF.getTag(this, "Orientation"));
                                                });
                                            }),
                                        t(function (t) {
                                            (o.width = u.width), (o.height = u.height);
                                            var e = h.options.resize.call(h, o, r, a, l),
                                                i = document.createElement("canvas"),
                                                n = i.getContext("2d");
                                            switch (((i.width = e.trgWidth), (i.height = e.trgHeight), 4 < t && ((i.width = e.trgHeight), (i.height = e.trgWidth)), t)) {
                                                case 2:
                                                    n.translate(i.width, 0), n.scale(-1, 1);
                                                    break;
                                                case 3:
                                                    n.translate(i.width, i.height), n.rotate(Math.PI);
                                                    break;
                                                case 4:
                                                    n.translate(0, i.height), n.scale(1, -1);
                                                    break;
                                                case 5:
                                                    n.rotate(0.5 * Math.PI), n.scale(1, -1);
                                                    break;
                                                case 6:
                                                    n.rotate(0.5 * Math.PI), n.translate(0, -i.width);
                                                    break;
                                                case 7:
                                                    n.rotate(0.5 * Math.PI), n.translate(i.height, -i.width), n.scale(-1, 1);
                                                    break;
                                                case 8:
                                                    n.rotate(-0.5 * Math.PI), n.translate(-i.height, 0);
                                            }
                                            drawImageIOSFix(n, u, null !==    e.srcX ? e.srcX : 0, null !==    e.srcY ? e.srcY : 0, e.srcWidth, e.srcHeight, null !==    e.trgX ? e.trgX : 0, null !==    e.trgY ? e.trgY : 0, e.trgWidth, e.trgHeight);
                                            var s = i.toDataURL("image/png");
                                            if (null !==    c) return c(s, i);
                                        })
                                    );
                                }),
                                null !==    c && (u.onerror = c),
                                (u.src = o.dataURL)
                            );
                        },
                    },
                    {
                        key: "processQueue",
                        value: function () {
                            var t = this.options.parallelUploads,
                                e = this.getUploadingFiles().length,
                                i = e;
                            if (!(t <= e)) {
                                var n = this.getQueuedFiles();
                                if (0 < n.length) {
                                    if (this.options.uploadMultiple) return this.processFiles(n.slice(0, t - e));
                                    for (; i < t; ) {
                                        if (!n.length) return;
                                        this.processFile(n.shift()), i++;
                                    }
                                }
                            }
                        },
                    },
                    {
                        key: "processFile",
                        value: function (t) {
                            return this.processFiles([t]);
                        },
                    },
                    {
                        key: "processFiles",
                        value: function (t) {
                            var e = !0,
                                i = !1,
                                n = void 0;
                            try {
                                for (var s, o = t[Symbol.iterator](); !(e = (s = o.next()).done); e = !0) {
                                    var r = s.value;
                                    (r.processing = !0), (r.status = T.UPLOADING), this.emit("processing", r);
                                }
                            } catch (t) {
                                (i = !0), (n = t);
                            } finally {
                                try {
                                    e || null === o.return || o.return();
                                } finally {
                                    if (i) throw n;
                                }
                            }
                            return this.options.uploadMultiple && this.emit("processingmultiple", t), this.uploadFiles(t);
                        },
                    },
                    {
                        key: "_getFilesWithXhr",
                        value: function (e) {
                            return this.files
                                .filter(function (t) {
                                    return t.xhr === e;
                                })
                                .map(function (t) {
                                    return t;
                                });
                        },
                    },
                    {
                        key: "cancelUpload",
                        value: function (t) {
                            if (t.status === T.UPLOADING) {
                                var e = this._getFilesWithXhr(t.xhr),
                                    i = !0,
                                    n = !1,
                                    s = void 0;
                                try {
                                    for (var o, r = e[Symbol.iterator](); !(i = (o = r.next()).done); i = !0) {
                                        o.value.status = T.CANCELED;
                                    }
                                } catch (t) {
                                    (n = !0), (s = t);
                                } finally {
                                    try {
                                        i || null === r.return || r.return();
                                    } finally {
                                        if (n) throw s;
                                    }
                                }
                                void 0 !== t.xhr && t.xhr.abort();
                                var a = !0,
                                    l = !1,
                                    c = void 0;
                                try {
                                    for (var h, u = e[Symbol.iterator](); !(a = (h = u.next()).done); a = !0) {
                                        var d = h.value;
                                        this.emit("canceled", d);
                                    }
                                } catch (t) {
                                    (l = !0), (c = t);
                                } finally {
                                    try {
                                        a || null === u.return || u.return();
                                    } finally {
                                        if (l) throw c;
                                    }
                                }
                                this.options.uploadMultiple && this.emit("canceledmultiple", e);
                            } else (t.status !== T.ADDED && t.status !== T.QUEUED) || ((t.status = T.CANCELED), this.emit("canceled", t), this.options.uploadMultiple && this.emit("canceledmultiple", [t]));
                            if (this.options.autoProcessQueue) return this.processQueue();
                        },
                    },
                    {
                        key: "resolveOption",
                        value: function (t) {
                            if ("function" !==    typeof t) return t;
                            for (var e = arguments.length, i = new Array(1 < e ? e - 1 : 0), n = 1; n < e; n++) i[n - 1] = arguments[n];
                            return t.apply(this, i);
                        },
                    },
                    {
                        key: "uploadFile",
                        value: function (t) {
                            return this.uploadFiles([t]);
                        },
                    },
                    {
                        key: "uploadFiles",
                        value: function (l) {
                            var c = this;
                            this._transformFiles(l, function (t) {
                                if (c.options.chunking) {
                                    var e = t[0];
                                    (l[0].upload.chunked = c.options.chunking && (c.options.forceChunking || e.size > c.options.chunkSize)), (l[0].upload.totalChunkCount = Math.ceil(e.size / c.options.chunkSize));
                                }
                                if (l[0].upload.chunked) {
                                    var s = l[0],
                                        o = t[0];
                                    s.upload.chunks = [];
                                    function n() {
                                        for (var t = 0; void 0 !== s.upload.chunks[t]; ) t++;
                                        if (!(t >= s.upload.totalChunkCount)) {
                                            0;
                                            var e = t * c.options.chunkSize,
                                                i = Math.min(e + c.options.chunkSize, s.size),
                                                n = { name: c._getParamName(0), data: o.webkitSlice ? o.webkitSlice(e, i) : o.slice(e, i), filename: s.upload.filename, chunkIndex: t };
                                            (s.upload.chunks[t] = { file: s, index: t, dataBlock: n, status: T.UPLOADING, progress: 0, retries: 0 }), c._uploadData(l, [n]);
                                        }
                                    }
                                    if (
                                        ((s.upload.finishedChunkUpload = function (t) {
                                            var e = !0;
                                            (t.status = T.SUCCESS), (t.dataBlock = null), (t.xhr = null);
                                            for (var i = 0; i < s.upload.totalChunkCount; i++) {
                                                if (void 0 === s.upload.chunks[i]) return n();
                                                s.upload.chunks[i].status !== T.SUCCESS && (e = !1);
                                            }
                                            e &&
                                                c.options.chunksUploaded(s, function () {
                                                    c._finished(l, "", null);
                                                });
                                        }),
                                        c.options.parallelChunkUploads)
                                    )
                                        for (var i = 0; i < s.upload.totalChunkCount; i++) n();
                                    else n();
                                } else {
                                    for (var r = [], a = 0; a < l.length; a++) r[a] = { name: c._getParamName(a), data: t[a], filename: l[a].upload.filename };
                                    c._uploadData(l, r);
                                }
                            });
                        },
                    },
                    {
                        key: "_getChunk",
                        value: function (t, e) {
                            for (var i = 0; i < t.upload.totalChunkCount; i++) if (void 0 !== t.upload.chunks[i] && t.upload.chunks[i].xhr === e) return t.upload.chunks[i];
                        },
                    },
                    {
                        key: "_uploadData",
                        value: function (e, t) {
                            var i = this,
                                n = new XMLHttpRequest(),
                                s = !0,
                                o = !1,
                                r = void 0;
                            try {
                                for (var a, l = e[Symbol.iterator](); !(s = (a = l.next()).done); s = !0) {
                                    a.value.xhr = n;
                                }
                            } catch (t) {
                                (o = !0), (r = t);
                            } finally {
                                try {
                                    s || null === l.return || l.return();
                                } finally {
                                    if (o) throw r;
                                }
                            }
                            e[0].upload.chunked && (e[0].upload.chunks[t[0].chunkIndex].xhr = n);
                            var c = this.resolveOption(this.options.method, e),
                                h = this.resolveOption(this.options.url, e);
                            n.open(c, h, !0),
                                (n.timeout = this.resolveOption(this.options.timeout, e)),
                                (n.withCredentials = !!this.options.withCredentials),
                                (n.onload = function (t) {
                                    i._finishedUploading(e, n, t);
                                }),
                                (n.ontimeout = function () {
                                    i._handleUploadError(e, n, "Request timedout after ".concat(i.options.timeout, " seconds"));
                                }),
                                (n.onerror = function () {
                                    i._handleUploadError(e, n);
                                }),
                                ((null !==    n.upload ? n.upload : n).onprogress = function (t) {
                                    return i._updateFilesUploadProgress(e, n, t);
                                });
                            var u = { Accept: "application/json", "Cache-Control": "no-cache", "X-Requested-With": "XMLHttpRequest" };
                            for (var d in (this.options.headers && T.extend(u, this.options.headers), u)) {
                                var p = u[d];
                                p && n.setRequestHeader(d, p);
                            }
                            var f = new FormData();
                            if (this.options.params) {
                                var g = this.options.params;
                                for (var m in ("function" === typeof g && (g = g.call(this, e, n, e[0].upload.chunked ? this._getChunk(e[0], n) : null)), g)) {
                                    var v = g[m];
                                    f.append(m, v);
                                }
                            }
                            var y = !0,
                                b = !1,
                                w = void 0;
                            try {
                                for (var _, x = e[Symbol.iterator](); !(y = (_ = x.next()).done); y = !0) {
                                    var C = _.value;
                                    this.emit("sending", C, n, f);
                                }
                            } catch (t) {
                                (b = !0), (w = t);
                            } finally {
                                try {
                                    y || null === x.return || x.return();
                                } finally {
                                    if (b) throw w;
                                }
                            }
                            this.options.uploadMultiple && this.emit("sendingmultiple", e, n, f), this._addFormElementData(f);
                            for (var D = 0; D < t.length; D++) {
                                var k = t[D];
                                f.append(k.name, k.data, k.filename);
                            }
                            this.submitRequest(n, f, e);
                        },
                    },
                    {
                        key: "_transformFiles",
                        value: function (i, n) {
                            function t(e) {
                                s.options.transformFile.call(s, i[e], function (t) {
                                    (o[e] = t), ++r === i.length && n(o);
                                });
                            }
                            for (var s = this, o = [], r = 0, e = 0; e < i.length; e++) t(e);
                        },
                    },
                    {
                        key: "_addFormElementData",
                        value: function (t) {
                            if ("FORM" === this.element.tagName) {
                                var e = !0,
                                    i = !1,
                                    n = void 0;
                                try {
                                    for (var s, o = this.element.querySelectorAll("input, textarea, select, button")[Symbol.iterator](); !(e = (s = o.next()).done); e = !0) {
                                        var r = s.value,
                                            a = r.getAttribute("name"),
                                            l = r.getAttribute("type");
                                        if (((l = l && l.toLowerCase()), null !==    a))
                                            if ("SELECT" === r.tagName && r.hasAttribute("multiple")) {
                                                var c = !0,
                                                    h = !1,
                                                    u = void 0;
                                                try {
                                                    for (var d, p = r.options[Symbol.iterator](); !(c = (d = p.next()).done); c = !0) {
                                                        var f = d.value;
                                                        f.selected && t.append(a, f.value);
                                                    }
                                                } catch (t) {
                                                    (h = !0), (u = t);
                                                } finally {
                                                    try {
                                                        c || null === p.return || p.return();
                                                    } finally {
                                                        if (h) throw u;
                                                    }
                                                }
                                            } else (!l || ("checkbox" !== l && "radio" !== l) || r.checked) && t.append(a, r.value);
                                    }
                                } catch (t) {
                                    (i = !0), (n = t);
                                } finally {
                                    try {
                                        e || null === o.return || o.return();
                                    } finally {
                                        if (i) throw n;
                                    }
                                }
                            }
                        },
                    },
                    {
                        key: "_updateFilesUploadProgress",
                        value: function (t, e, i) {
                            var n;
                            if (void 0 !== i) {
                                if (((n = (100 * i.loaded) / i.total), t[0].upload.chunked)) {
                                    var s = t[0],
                                        o = this._getChunk(s, e);
                                    (o.progress = n), (o.total = i.total), (o.bytesSent = i.loaded);
                                    (s.upload.progress = 0), (s.upload.total = 0);
                                    for (var r = (s.upload.bytesSent = 0); r < s.upload.totalChunkCount; r++)
                                        void 0 !== s.upload.chunks[r] &&
                                            void 0 !== s.upload.chunks[r].progress &&
                                            ((s.upload.progress += s.upload.chunks[r].progress), (s.upload.total += s.upload.chunks[r].total), (s.upload.bytesSent += s.upload.chunks[r].bytesSent));
                                    s.upload.progress = s.upload.progress / s.upload.totalChunkCount;
                                } else {
                                    var a = !0,
                                        l = !1,
                                        c = void 0;
                                    try {
                                        for (var h, u = t[Symbol.iterator](); !(a = (h = u.next()).done); a = !0) {
                                            var d = h.value;
                                            (d.upload.progress = n), (d.upload.total = i.total), (d.upload.bytesSent = i.loaded);
                                        }
                                    } catch (t) {
                                        (l = !0), (c = t);
                                    } finally {
                                        try {
                                            a || null === u.return || u.return();
                                        } finally {
                                            if (l) throw c;
                                        }
                                    }
                                }
                                var p = !0,
                                    f = !1,
                                    g = void 0;
                                try {
                                    for (var m, v = t[Symbol.iterator](); !(p = (m = v.next()).done); p = !0) {
                                        var y = m.value;
                                        this.emit("uploadprogress", y, y.upload.progress, y.upload.bytesSent);
                                    }
                                } catch (t) {
                                    (f = !0), (g = t);
                                } finally {
                                    try {
                                        p || null === v.return || v.return();
                                    } finally {
                                        if (f) throw g;
                                    }
                                }
                            } else {
                                var b = !0,
                                    w = !0,
                                    _ = !(n = 100),
                                    x = void 0;
                                try {
                                    for (var C, D = t[Symbol.iterator](); !(w = (C = D.next()).done); w = !0) {
                                        var k = C.value;
                                        (100 === k.upload.progress && k.upload.bytesSent === k.upload.total) || (b = !1), (k.upload.progress = n), (k.upload.bytesSent = k.upload.total);
                                    }
                                } catch (t) {
                                    (_ = !0), (x = t);
                                } finally {
                                    try {
                                        w || null === D.return || D.return();
                                    } finally {
                                        if (_) throw x;
                                    }
                                }
                                if (b) return;
                                var T = !0,
                                    S = !1,
                                    E = void 0;
                                try {
                                    for (var A, P = t[Symbol.iterator](); !(T = (A = P.next()).done); T = !0) {
                                        var $ = A.value;
                                        this.emit("uploadprogress", $, n, $.upload.bytesSent);
                                    }
                                } catch (t) {
                                    (S = !0), (E = t);
                                } finally {
                                    try {
                                        T || null === P.return || P.return();
                                    } finally {
                                        if (S) throw E;
                                    }
                                }
                            }
                        },
                    },
                    {
                        key: "_finishedUploading",
                        value: function (t, e, i) {
                            var n;
                            if (t[0].status !== T.CANCELED && 4 === e.readyState) {
                                if ("arraybuffer" !== e.responseType && "blob" !== e.responseType && ((n = e.responseText), e.getResponseHeader("content-type") && ~e.getResponseHeader("content-type").indexOf("application/json")))
                                    try {
                                        n = JSON.parse(n);
                                    } catch (t) {
                                        (i = t), (n = "Invalid JSON response from server.");
                                    }
                                this._updateFilesUploadProgress(t),
                                    200 <= e.status && e.status < 300 ? (t[0].upload.chunked ? t[0].upload.finishedChunkUpload(this._getChunk(t[0], e)) : this._finished(t, n, i)) : this._handleUploadError(t, e, n);
                            }
                        },
                    },
                    {
                        key: "_handleUploadError",
                        value: function (t, e, i) {
                            if (t[0].status !== T.CANCELED) {
                                if (t[0].upload.chunked && this.options.retryChunks) {
                                    var n = this._getChunk(t[0], e);
                                    if (n.retries++ < this.options.retryChunksLimit) return void this._uploadData(t, [n.dataBlock]);
                                    console.warn("Retried this chunk too often. Giving up.");
                                }
                                this._errorProcessing(t, i || this.options.dictResponseError.replace("{{statusCode}}", e.status), e);
                            }
                        },
                    },
                    {
                        key: "submitRequest",
                        value: function (t, e) {
                            t.send(e);
                        },
                    },
                    {
                        key: "_finished",
                        value: function (t, e, i) {
                            var n = !0,
                                s = !1,
                                o = void 0;
                            try {
                                for (var r, a = t[Symbol.iterator](); !(n = (r = a.next()).done); n = !0) {
                                    var l = r.value;
                                    (l.status = T.SUCCESS), this.emit("success", l, e, i), this.emit("complete", l);
                                }
                            } catch (t) {
                                (s = !0), (o = t);
                            } finally {
                                try {
                                    n || null === a.return || a.return();
                                } finally {
                                    if (s) throw o;
                                }
                            }
                            if ((this.options.uploadMultiple && (this.emit("successmultiple", t, e, i), this.emit("completemultiple", t)), this.options.autoProcessQueue)) return this.processQueue();
                        },
                    },
                    {
                        key: "_errorProcessing",
                        value: function (t, e, i) {
                            var n = !0,
                                s = !1,
                                o = void 0;
                            try {
                                for (var r, a = t[Symbol.iterator](); !(n = (r = a.next()).done); n = !0) {
                                    var l = r.value;
                                    (l.status = T.ERROR), this.emit("error", l, e, i), this.emit("complete", l);
                                }
                            } catch (t) {
                                (s = !0), (o = t);
                            } finally {
                                try {
                                    n || null === a.return || a.return();
                                } finally {
                                    if (s) throw o;
                                }
                            }
                            if ((this.options.uploadMultiple && (this.emit("errormultiple", t, e, i), this.emit("completemultiple", t)), this.options.autoProcessQueue)) return this.processQueue();
                        },
                    },
                ],
                [
                    {
                        key: "uuidv4",
                        value: function () {
                            return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g, function (t) {
                                var e = (16 * Math.random()) | 0;
                                return ("x" === t ? e : (3 & e) | 8).toString(16);
                            });
                        },
                    },
                ]
            ),
            T
        );
    })();
Dropzone.initClass(),
    (Dropzone.version = "5.7.0"),
    (Dropzone.options = {}),
    (Dropzone.optionsForElement = function (t) {
        return t.getAttribute("id") ? Dropzone.options[camelize(t.getAttribute("id"))] : void 0;
    }),
    (Dropzone.instances = []),
    (Dropzone.forElement = function (t) {
        if (("string" === typeof t && (t = document.querySelector(t)), null === (null !==    t ? t.dropzone : void 0)))
            throw new Error("No Dropzone found for given element. This is probably because you're trying to access it before Dropzone had the time to initialize. Use the `init` option to setup any additional observers on your Dropzone.");
        return t.dropzone;
    }),
    (Dropzone.autoDiscover = !0),
    (Dropzone.discover = function () {
        var l;
        if (document.querySelectorAll) l = document.querySelectorAll(".dropzone");
        else {
            l = [];
            function t(a) {
                return (function () {
                    var t = [],
                        e = !0,
                        i = !1,
                        n = void 0;
                    try {
                        for (var s, o = a[Symbol.iterator](); !(e = (s = o.next()).done); e = !0) {
                            var r = s.value;
                            /(^| )dropzone($| )/.test(r.className) ? t.push(l.push(r)) : t.push(void 0);
                        }
                    } catch (t) {
                        (i = !0), (n = t);
                    } finally {
                        try {
                            e || null === o.return || o.return();
                        } finally {
                            if (i) throw n;
                        }
                    }
                    return t;
                })();
            }
            t(document.getElementsByTagName("div")), t(document.getElementsByTagName("form"));
        }
        return (function () {
            var t = [],
                e = !0,
                i = !1,
                n = void 0;
            try {
                for (var s, o = l[Symbol.iterator](); !(e = (s = o.next()).done); e = !0) {
                    var r = s.value;
                    !1 !== Dropzone.optionsForElement(r) ? t.push(new Dropzone(r)) : t.push(void 0);
                }
            } catch (t) {
                (i = !0), (n = t);
            } finally {
                try {
                    e || null === o.return || o.return();
                } finally {
                    if (i) throw n;
                }
            }
            return t;
        })();
    }),
    (Dropzone.blacklistedBrowsers = [/opera.*(Macintosh|Windows Phone).*version\/12/i]),
    (Dropzone.isBrowserSupported = function () {
        var t = !0;
        if (window.File && window.FileReader && window.FileList && window.Blob && window.FormData && document.querySelector)
            if ("classList" in document.createElement("a")) {
                var e = !0,
                    i = !1,
                    n = void 0;
                try {
                    for (var s, o = Dropzone.blacklistedBrowsers[Symbol.iterator](); !(e = (s = o.next()).done); e = !0) {
                        s.value.test(navigator.userAgent) && (t = !1);
                    }
                } catch (t) {
                    (i = !0), (n = t);
                } finally {
                    try {
                        e || null === o.return || o.return();
                    } finally {
                        if (i) throw n;
                    }
                }
            } else t = !1;
        else t = !1;
        return t;
    }),
    (Dropzone.dataURItoBlob = function (t) {
        for (var e = atob(t.split(",")[1]), i = t.split(",")[0].split(":")[1].split(";")[0], n = new ArrayBuffer(e.length), s = new Uint8Array(n), o = 0, r = e.length, a = 0 <= r; a ? o <= r : r <= o; a ? o++ : o--) s[o] = e.charCodeAt(o);
        return new Blob([n], { type: i });
    });
var without = function (t, e) {
        return t
            .filter(function (t) {
                return t !== e;
            })
            .map(function (t) {
                return t;
            });
    },
    camelize = function (t) {
        return t.replace(/[\-_](\w)/g, function (t) {
            return t.charAt(1).toUpperCase();
        });
    };
(Dropzone.createElement = function (t) {
    var e = document.createElement("div");
    return (e.innerHTML = t), e.childNodes[0];
}),
    (Dropzone.elementInside = function (t, e) {
        if (t === e) return !0;
        for (; (t = t.parentNode); ) if (t === e) return !0;
        return !1;
    }),
    (Dropzone.getElement = function (t, e) {
        var i;
        if (("string" === typeof t ? (i = document.querySelector(t)) : null !==    t.nodeType && (i = t), null === i)) throw new Error("Invalid `".concat(e, "` option provided. Please provide a CSS selector or a plain HTML element."));
        return i;
    }),
    (Dropzone.getElements = function (t, e) {
        var i, n;
        if (t instanceof Array) {
            n = [];
            try {
                var s = !0,
                    o = !1,
                    r = void 0;
                try {
                    for (var a, l = t[Symbol.iterator](); !(s = (a = l.next()).done); s = !0) (i = a.value), n.push(this.getElement(i, e));
                } catch (t) {
                    (o = !0), (r = t);
                } finally {
                    try {
                        s || null === l.return || l.return();
                    } finally {
                        if (o) throw r;
                    }
                }
            } catch (t) {
                n = null;
            }
        } else if ("string" === typeof t) {
            var c = !0,
                h = !(n = []),
                u = void 0;
            try {
                for (var d, p = document.querySelectorAll(t)[Symbol.iterator](); !(c = (d = p.next()).done); c = !0) (i = d.value), n.push(i);
            } catch (t) {
                (h = !0), (u = t);
            } finally {
                try {
                    c || null === p.return || p.return();
                } finally {
                    if (h) throw u;
                }
            }
        } else null !==    t.nodeType && (n = [t]);
        if (null === n || !n.length) throw new Error("Invalid `".concat(e, "` option provided. Please provide a CSS selector, a plain HTML element or a list of those."));
        return n;
    }),
    (Dropzone.confirm = function (t, e, i) {
        return window.confirm(t) ? e() : null !==    i ? i() : void 0;
    }),
    (Dropzone.isValidFile = function (t, e) {
        if (!e) return !0;
        e = e.split(",");
        var i = t.type,
            n = i.replace(/\/.*$/, ""),
            s = !0,
            o = !1,
            r = void 0;
        try {
            for (var a, l = e[Symbol.iterator](); !(s = (a = l.next()).done); s = !0) {
                var c = a.value;
                if ("." === (c = c.trim()).charAt(0)) {
                    if (-1 !== t.name.toLowerCase().indexOf(c.toLowerCase(), t.name.length - c.length)) return !0;
                } else if (/\/\*$/.test(c)) {
                    if (n === c.replace(/\/.*$/, "")) return !0;
                } else if (i === c) return !0;
            }
        } catch (t) {
            (o = !0), (r = t);
        } finally {
            try {
                s || null === l.return || l.return();
            } finally {
                if (o) throw r;
            }
        }
        return !1;
    }),
    "undefined" !==    typeof jQuery &&
        null !== jQuery &&
        (jQuery.fn.dropzone = function (t) {
            return this.each(function () {
                return new Dropzone(this, t);
            });
        }),
    "undefined" !==    typeof module && null !== module ? (module.exports = Dropzone) : (window.Dropzone = Dropzone),
    (Dropzone.ADDED = "added"),
    (Dropzone.QUEUED = "queued"),
    (Dropzone.ACCEPTED = Dropzone.QUEUED),
    (Dropzone.UPLOADING = "uploading"),
    (Dropzone.PROCESSING = Dropzone.UPLOADING),
    (Dropzone.CANCELED = "canceled"),
    (Dropzone.ERROR = "error"),
    (Dropzone.SUCCESS = "success");
var detectVerticalSquash = function (t) {
        t.naturalWidth;
        var e = t.naturalHeight,
            i = document.createElement("canvas");
        (i.width = 1), (i.height = e);
        var n = i.getContext("2d");
        n.drawImage(t, 0, 0);
        for (var s = n.getImageData(1, 0, 1, e).data, o = 0, r = e, a = e; o < a; ) {
            0 === s[4 * (a - 1) + 3] ? (r = a) : (o = a), (a = (r + o) >> 1);
        }
        var l = a / e;
        return 0 === l ? 1 : l;
    },
    drawImageIOSFix = function (t, e, i, n, s, o, r, a, l, c) {
        var h = detectVerticalSquash(e);
        return t.drawImage(e, i, n, s, o, r, a, l, c / h);
    },
    ExifRestore = (function () {
        function t() {
            _classCallCheck(this, t);
        }
        return (
            _createClass(t, null, [
                {
                    key: "initClass",
                    value: function () {
                        this.KEY_STR = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
                    },
                },
                {
                    key: "encode64",
                    value: function (t) {
                        for (
                            var e = "", i = void 0, n = void 0, s = "", o = void 0, r = void 0, a = void 0, l = "", c = 0;
                            (o = (i = t[c++]) >> 2),
                                (r = ((3 & i) << 4) | ((n = t[c++]) >> 4)),
                                (a = ((15 & n) << 2) | ((s = t[c++]) >> 6)),
                                (l = 63 & s),
                                isNaN(n) ? (a = l = 64) : isNaN(s) && (l = 64),
                                (e = e + this.KEY_STR.charAt(o) + this.KEY_STR.charAt(r) + this.KEY_STR.charAt(a) + this.KEY_STR.charAt(l)),
                                (i = n = s = ""),
                                (o = r = a = l = ""),
                                c < t.length;

                        );
                        return e;
                    },
                },
                {
                    key: "restore",
                    value: function (t, e) {
                        if (!t.match("data:image/jpeg;base64,")) return e;
                        var i = this.decode64(t.replace("data:image/jpeg;base64,", "")),
                            n = this.slice2Segments(i),
                            s = this.exifManipulation(e, n);
                        return "data:image/jpeg;base64,".concat(this.encode64(s));
                    },
                },
                {
                    key: "exifManipulation",
                    value: function (t, e) {
                        var i = this.getExifArray(e),
                            n = this.insertExif(t, i);
                        return new Uint8Array(n);
                    },
                },
                {
                    key: "getExifArray",
                    value: function (t) {
                        for (var e = void 0, i = 0; i < t.length; ) {
                            if ((255 === (e = t[i])[0]) & (225 === e[1])) return e;
                            i++;
                        }
                        return [];
                    },
                },
                {
                    key: "insertExif",
                    value: function (t, e) {
                        var i = t.replace("data:image/jpeg;base64,", ""),
                            n = this.decode64(i),
                            s = n.indexOf(255, 3),
                            o = n.slice(0, s),
                            r = n.slice(s),
                            a = o;
                        return (a = (a = a.concat(e)).concat(r));
                    },
                },
                {
                    key: "slice2Segments",
                    value: function (t) {
                        for (var e = 0, i = []; ; ) {
                            if ((255 === t[e]) & (218 === t[e + 1])) break;
                            if ((255 === t[e]) & (216 === t[e + 1])) e += 2;
                            else {
                                var n = e + (256 * t[e + 2] + t[e + 3]) + 2,
                                    s = t.slice(e, n);
                                i.push(s), (e = n);
                            }
                            if (e > t.length) break;
                        }
                        return i;
                    },
                },
                {
                    key: "decode64",
                    value: function (t) {
                        var e = void 0,
                            i = void 0,
                            n = "",
                            s = void 0,
                            o = void 0,
                            r = "",
                            a = 0,
                            l = [];
                        for (
                            /[^A-Za-z0-9\+\/\=]/g.exec(t) && console.warn("There were invalid base64 characters in the input text.\nValid base64 characters are A-Z, a-z, 0-9, '+', '/',and '='\nExpect errors in decoding."),
                                t = t.replace(/[^A-Za-z0-9\+\/\=]/g, "");
                            (e = (this.KEY_STR.indexOf(t.charAt(a++)) << 2) | ((s = this.KEY_STR.indexOf(t.charAt(a++))) >> 4)),
                                (i = ((15 & s) << 4) | ((o = this.KEY_STR.indexOf(t.charAt(a++))) >> 2)),
                                (n = ((3 & o) << 6) | (r = this.KEY_STR.indexOf(t.charAt(a++)))),
                                l.push(e),
                                64 !== o && l.push(i),
                                64 !== r && l.push(n),
                                (e = i = n = ""),
                                (s = o = r = ""),
                                a < t.length;

                        );
                        return l;
                    },
                },
            ]),
            t
        );
    })();
ExifRestore.initClass();
var contentLoaded = function (e, i) {
    function n(t) {
        if ("readystatechange" !== t.type || "complete" === o.readyState) return ("load" === t.type ? e : o)[l](c + t.type, n, !1), !s && (s = !0) ? i.call(e, t.type || t) : void 0;
    }
    var s = !1,
        t = !0,
        o = e.document,
        r = o.documentElement,
        a = o.addEventListener ? "addEventListener" : "attachEvent",
        l = o.addEventListener ? "removeEventListener" : "detachEvent",
        c = o.addEventListener ? "" : "on";
    if ("complete" !== o.readyState) {
        if (o.createEventObject && r.doScroll) {
            try {
                t = !e.frameElement;
            } catch (t) {}
            t &&
                !(function e() {
                    try {
                        r.doScroll("left");
                    } catch (t) {
                        return void setTimeout(e, 50);
                    }
                    return n("poll");
                })();
        }
        return o[a](c + "DOMContentLoaded", n, !1), o[a](c + "readystatechange", n, !1), e[a](c + "load", n, !1);
    }
};
function __guard__(t, e) {
    return null !==    t ? e(t) : void 0;
}
function __guardMethod__(t, e, i) {
    return null !==    t && "function" === typeof t[e] ? i(t, e) : void 0;
}
(Dropzone._autoDiscoverFunction = function () {
    if (Dropzone.autoDiscover) return Dropzone.discover();
}),
    contentLoaded(window, Dropzone._autoDiscoverFunction),
    (function (t, e) {
        "object" === typeof exports && "object" === typeof module ? (module.exports = e()) : "function" === typeof define && define.amd ? define([], e) : "object" === typeof exports ? (exports.ClipboardJS = e()) : (t.ClipboardJS = e());
    })(this, function () {
        return (
            (n = {}),
            (s.m = i = [
                function (t, e) {
                    t.exports = function (t) {
                        var e;
                        if ("SELECT" === t.nodeName) t.focus(), (e = t.value);
                        else if ("INPUT" === t.nodeName || "TEXTAREA" === t.nodeName) {
                            var i = t.hasAttribute("readonly");
                            i || t.setAttribute("readonly", ""), t.select(), t.setSelectionRange(0, t.value.length), i || t.removeAttribute("readonly"), (e = t.value);
                        } else {
                            t.hasAttribute("contenteditable") && t.focus();
                            var n = window.getSelection(),
                                s = document.createRange();
                            s.selectNodeContents(t), n.removeAllRanges(), n.addRange(s), (e = n.toString());
                        }
                        return e;
                    };
                },
                function (t, e) {
                    function i() {}
                    (i.prototype = {
                        on: function (t, e, i) {
                            var n = this.e || (this.e = {});
                            return (n[t] || (n[t] = [])).push({ fn: e, ctx: i }), this;
                        },
                        once: function (t, e, i) {
                            var n = this;
                            function s() {
                                n.off(t, s), e.apply(i, arguments);
                            }
                            return (s._ = e), this.on(t, s, i);
                        },
                        emit: function (t) {
                            for (var e = [].slice.call(arguments, 1), i = ((this.e || (this.e = {}))[t] || []).slice(), n = 0, s = i.length; n < s; n++) i[n].fn.apply(i[n].ctx, e);
                            return this;
                        },
                        off: function (t, e) {
                            var i = this.e || (this.e = {}),
                                n = i[t],
                                s = [];
                            if (n && e) for (var o = 0, r = n.length; o < r; o++) n[o].fn !== e && n[o].fn._ !== e && s.push(n[o]);
                            return s.length ? (i[t] = s) : delete i[t], this;
                        },
                    }),
                        (t.exports = i),
                        (t.exports.TinyEmitter = i);
                },
                function (t, e, i) {
                    var d = i(3),
                        p = i(4);
                    t.exports = function (t, e, i) {
                        if (!t && !e && !i) throw new Error("Missing required arguments");
                        if (!d.string(e)) throw new TypeError("Second argument must be a String");
                        if (!d.fn(i)) throw new TypeError("Third argument must be a Function");
                        if (d.node(t))
                            return (
                                (h = e),
                                (u = i),
                                (c = t).addEventListener(h, u),
                                {
                                    destroy: function () {
                                        c.removeEventListener(h, u);
                                    },
                                }
                            );
                        if (d.nodeList(t))
                            return (
                                (r = t),
                                (a = e),
                                (l = i),
                                Array.prototype.forEach.call(r, function (t) {
                                    t.addEventListener(a, l);
                                }),
                                {
                                    destroy: function () {
                                        Array.prototype.forEach.call(r, function (t) {
                                            t.removeEventListener(a, l);
                                        });
                                    },
                                }
                            );
                        if (d.string(t)) return (n = t), (s = e), (o = i), p(document.body, n, s, o);
                        throw new TypeError("First argument must be a String, HTMLElement, HTMLCollection, or NodeList");
                        var n, s, o, r, a, l, c, h, u;
                    };
                },
                function (t, i) {
                    (i.node = function (t) {
                        return void 0 !== t && t instanceof HTMLElement && 1 === t.nodeType;
                    }),
                        (i.nodeList = function (t) {
                            var e = Object.prototype.toString.call(t);
                            return void 0 !== t && ("[object NodeList]" === e || "[object HTMLCollection]" === e) && "length" in t && (0 === t.length || i.node(t[0]));
                        }),
                        (i.string = function (t) {
                            return "string" === typeof t || t instanceof String;
                        }),
                        (i.fn = function (t) {
                            return "[object Function]" === Object.prototype.toString.call(t);
                        });
                },
                function (t, e, i) {
                    var r = i(5);
                    function o(t, e, i, n, s) {
                        var o = function (e, i, t, n) {
                            return function (t) {
                                (t.delegateTarget = r(t.target, i)), t.delegateTarget && n.call(e, t);
                            };
                        }.apply(this, arguments);
                        return (
                            t.addEventListener(i, o, s),
                            {
                                destroy: function () {
                                    t.removeEventListener(i, o, s);
                                },
                            }
                        );
                    }
                    t.exports = function (t, e, i, n, s) {
                        return "function" === typeof t.addEventListener
                            ? o.apply(null, arguments)
                            : "function" === typeof i
                            ? o.bind(null, document).apply(null, arguments)
                            : ("string" === typeof t && (t = document.querySelectorAll(t)),
                              Array.prototype.map.call(t, function (t) {
                                  return o(t, e, i, n, s);
                              }));
                    };
                },
                function (t, e) {
                    if ("undefined" !==    typeof Element && !Element.prototype.matches) {
                        var i = Element.prototype;
                        i.matches = i.matchesSelector || i.mozMatchesSelector || i.msMatchesSelector || i.oMatchesSelector || i.webkitMatchesSelector;
                    }
                    t.exports = function (t, e) {
                        for (; t && 9 !== t.nodeType; ) {
                            if ("function" === typeof t.matches && t.matches(e)) return t;
                            t = t.parentNode;
                        }
                    };
                },
                function (t, e, i) {
                    "use strict";
                    i.r(e);
                    var n = i(0),
                        s = i.n(n),
                        o =
                            "function" === typeof Symbol && "symbol" === typeof Symbol.iterator
                                ? function (t) {
                                      return typeof t;
                                  }
                                : function (t) {
                                      return t && "function" === typeof Symbol && t.constructor === Symbol && t !== Symbol.prototype ? "symbol" : typeof t;
                                  };
                    function r(t, e) {
                        for (var i = 0; i < e.length; i++) {
                            var n = e[i];
                            (n.enumerable = n.enumerable || !1), (n.configurable = !0), "value" in n && (n.writable = !0), Object.defineProperty(t, n.key, n);
                        }
                    }
                    function a(t) {
                        !(function (t, e) {
                            if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function");
                        })(this, a),
                            this.resolveOptions(t),
                            this.initSelection();
                    }
                    var l =
                            ((function (t, e, i) {
                                return e && r(t.prototype, e), i && r(t, i), t;
                            })(a, [
                                {
                                    key: "resolveOptions",
                                    value: function (t) {
                                        var e = 0 < arguments.length && void 0 !== t ? t : {};
                                        (this.action = e.action), (this.container = e.container), (this.emitter = e.emitter), (this.target = e.target), (this.text = e.text), (this.trigger = e.trigger), (this.selectedText = "");
                                    },
                                },
                                {
                                    key: "initSelection",
                                    value: function () {
                                        this.text ? this.selectFake() : this.target && this.selectTarget();
                                    },
                                },
                                {
                                    key: "selectFake",
                                    value: function () {
                                        var t = this,
                                            e = "rtl" === document.documentElement.getAttribute("dir");
                                        this.removeFake(),
                                            (this.fakeHandlerCallback = function () {
                                                return t.removeFake();
                                            }),
                                            (this.fakeHandler = this.container.addEventListener("click", this.fakeHandlerCallback) || !0),
                                            (this.fakeElem = document.createElement("textarea")),
                                            (this.fakeElem.style.fontSize = "12pt"),
                                            (this.fakeElem.style.border = "0"),
                                            (this.fakeElem.style.padding = "0"),
                                            (this.fakeElem.style.margin = "0"),
                                            (this.fakeElem.style.position = "absolute"),
                                            (this.fakeElem.style[e ? "right" : "left"] = "-9999px");
                                        var i = window.pageYOffset || document.documentElement.scrollTop;
                                        (this.fakeElem.style.top = i + "px"),
                                            this.fakeElem.setAttribute("readonly", ""),
                                            (this.fakeElem.value = this.text),
                                            this.container.appendChild(this.fakeElem),
                                            (this.selectedText = s()(this.fakeElem)),
                                            this.copyText();
                                    },
                                },
                                {
                                    key: "removeFake",
                                    value: function () {
                                        this.fakeHandler && (this.container.removeEventListener("click", this.fakeHandlerCallback), (this.fakeHandler = null), (this.fakeHandlerCallback = null)),
                                            this.fakeElem && (this.container.removeChild(this.fakeElem), (this.fakeElem = null));
                                    },
                                },
                                {
                                    key: "selectTarget",
                                    value: function () {
                                        (this.selectedText = s()(this.target)), this.copyText();
                                    },
                                },
                                {
                                    key: "copyText",
                                    value: function () {
                                        var e = void 0;
                                        try {
                                            e = document.execCommand(this.action);
                                        } catch (t) {
                                            e = !1;
                                        }
                                        this.handleResult(e);
                                    },
                                },
                                {
                                    key: "handleResult",
                                    value: function (t) {
                                        this.emitter.emit(t ? "success" : "error", { action: this.action, text: this.selectedText, trigger: this.trigger, clearSelection: this.clearSelection.bind(this) });
                                    },
                                },
                                {
                                    key: "clearSelection",
                                    value: function () {
                                        this.trigger && this.trigger.focus(), document.activeElement.blur(), window.getSelection().removeAllRanges();
                                    },
                                },
                                {
                                    key: "destroy",
                                    value: function () {
                                        this.removeFake();
                                    },
                                },
                                {
                                    key: "action",
                                    set: function (t) {
                                        var e = 0 < arguments.length && void 0 !== t ? t : "copy";
                                        if (((this._action = e), "copy" !== this._action && "cut" !== this._action)) throw new Error('Invalid "action" value, use either "copy" or "cut"');
                                    },
                                    get: function () {
                                        return this._action;
                                    },
                                },
                                {
                                    key: "target",
                                    set: function (t) {
                                        if (void 0 !== t) {
                                            if (!t || "object" !== (void 0 === t ? "undefined" : o(t)) || 1 !== t.nodeType) throw new Error('Invalid "target" value, use a valid Element');
                                            if ("copy" === this.action && t.hasAttribute("disabled")) throw new Error('Invalid "target" attribute. Please use "readonly" instead of "disabled" attribute');
                                            if ("cut" === this.action && (t.hasAttribute("readonly") || t.hasAttribute("disabled")))
                                                throw new Error('Invalid "target" attribute. You can\'t cut text from elements with "readonly" or "disabled" attributes');
                                            this._target = t;
                                        }
                                    },
                                    get: function () {
                                        return this._target;
                                    },
                                },
                            ]),
                            a),
                        c = i(1),
                        h = i.n(c),
                        u = i(2),
                        d = i.n(u),
                        p =
                            "function" === typeof Symbol && "symbol" === typeof Symbol.iterator
                                ? function (t) {
                                      return typeof t;
                                  }
                                : function (t) {
                                      return t && "function" === typeof Symbol && t.constructor === Symbol && t !== Symbol.prototype ? "symbol" : typeof t;
                                  },
                        f = function (t, e, i) {
                            return e && g(t.prototype, e), i && g(t, i), t;
                        };
                    function g(t, e) {
                        for (var i = 0; i < e.length; i++) {
                            var n = e[i];
                            (n.enumerable = n.enumerable || !1), (n.configurable = !0), "value" in n && (n.writable = !0), Object.defineProperty(t, n.key, n);
                        }
                    }
                    var m =
                        ((function (t, e) {
                            if ("function" !==    typeof e && null !== e) throw new TypeError("Super expression must either be null or a function, not " + typeof e);
                            (t.prototype = Object.create(e && e.prototype, { constructor: { value: t, enumerable: !1, writable: !0, configurable: !0 } })), e && (Object.setPrototypeOf ? Object.setPrototypeOf(t, e) : (t.__proto__ = e));
                        })(v, h.a),
                        f(
                            v,
                            [
                                {
                                    key: "resolveOptions",
                                    value: function (t) {
                                        var e = 0 < arguments.length && void 0 !== t ? t : {};
                                        (this.action = "function" === typeof e.action ? e.action : this.defaultAction),
                                            (this.target = "function" === typeof e.target ? e.target : this.defaultTarget),
                                            (this.text = "function" === typeof e.text ? e.text : this.defaultText),
                                            (this.container = "object" === p(e.container) ? e.container : document.body);
                                    },
                                },
                                {
                                    key: "listenClick",
                                    value: function (t) {
                                        var e = this;
                                        this.listener = d()(t, "click", function (t) {
                                            return e.onClick(t);
                                        });
                                    },
                                },
                                {
                                    key: "onClick",
                                    value: function (t) {
                                        var e = t.delegateTarget || t.currentTarget;
                                        this.clipboardAction && (this.clipboardAction = null),
                                            (this.clipboardAction = new l({ action: this.action(e), target: this.target(e), text: this.text(e), container: this.container, trigger: e, emitter: this }));
                                    },
                                },
                                {
                                    key: "defaultAction",
                                    value: function (t) {
                                        return y("action", t);
                                    },
                                },
                                {
                                    key: "defaultTarget",
                                    value: function (t) {
                                        var e = y("target", t);
                                        if (e) return document.querySelector(e);
                                    },
                                },
                                {
                                    key: "defaultText",
                                    value: function (t) {
                                        return y("text", t);
                                    },
                                },
                                {
                                    key: "destroy",
                                    value: function () {
                                        this.listener.destroy(), this.clipboardAction && (this.clipboardAction.destroy(), (this.clipboardAction = null));
                                    },
                                },
                            ],
                            [
                                {
                                    key: "isSupported",
                                    value: function (t) {
                                        var e = 0 < arguments.length && void 0 !== t ? t : ["copy", "cut"],
                                            i = "string" === typeof e ? [e] : e,
                                            n = !!document.queryCommandSupported;
                                        return (
                                            i.forEach(function (t) {
                                                n = n && !!document.queryCommandSupported(t);
                                            }),
                                            n
                                        );
                                    },
                                },
                            ]
                        ),
                        v);
                    function v(t, e) {
                        !(function (t, e) {
                            if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function");
                        })(this, v);
                        var i = (function (t, e) {
                            if (!t) throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
                            return !e || ("object" !==    typeof e && "function" !==    typeof e) ? t : e;
                        })(this, (v.__proto__ || Object.getPrototypeOf(v)).call(this));
                        return i.resolveOptions(e), i.listenClick(t), i;
                    }
                    function y(t, e) {
                        var i = "data-clipboard-" + t;
                        if (e.hasAttribute(i)) return e.getAttribute(i);
                    }
                    e.default = m;
                },
            ]),
            (s.c = n),
            (s.d = function (t, e, i) {
                s.o(t, e) || Object.defineProperty(t, e, { enumerable: !0, get: i });
            }),
            (s.r = function (t) {
                "undefined" !==    typeof Symbol && Symbol.toStringTag && Object.defineProperty(t, Symbol.toStringTag, { value: "Module" }), Object.defineProperty(t, "__esModule", { value: !0 });
            }),
            (s.t = function (e, t) {
                if ((1 & t && (e = s(e)), 8 & t)) return e;
                if (4 & t && "object" === typeof e && e && e.__esModule) return e;
                var i = Object.create(null);
                if ((s.r(i), Object.defineProperty(i, "default", { enumerable: !0, value: e }), 2 & t && "string" !==    typeof e))
                    for (var n in e)
                        s.d(
                            i,
                            n,
                            function (t) {
                                return e[t];
                            }.bind(null, n)
                        );
                return i;
            }),
            (s.n = function (t) {
                var e =
                    t && t.__esModule
                        ? function () {
                              return t.default;
                          }
                        : function () {
                              return t;
                          };
                return s.d(e, "a", e), e;
            }),
            (s.o = function (t, e) {
                return Object.prototype.hasOwnProperty.call(t, e);
            }),
            (s.p = ""),
            s((s.s = 6)).default
        );
        function s(t) {
            if (n[t]) return n[t].exports;
            var e = (n[t] = { i: t, l: !1, exports: {} });
            return i[t].call(e.exports, e, e.exports, s), (e.l = !0), e.exports;
        }
        var i, n;
    });
