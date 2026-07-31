// playwright/render-invoice.ts
import { chromium } from "playwright";

// node_modules/@otplib/core/dist/index.js
var i = class extends Error {
  constructor(e, t2) {
    super(e, t2), this.name = "OTPError";
  }
};
var x = class extends i {
  constructor(e) {
    super(e), this.name = "SecretError";
  }
};
var h = class extends x {
  constructor(e, t2) {
    super(`Secret must be at least ${e} bytes (${e * 8} bits), got ${t2} bytes`), this.name = "SecretTooShortError";
  }
};
var P = class extends x {
  constructor(e, t2) {
    super(`Secret must not exceed ${e} bytes, got ${t2} bytes`), this.name = "SecretTooLongError";
  }
};
var m = class extends i {
  constructor(e) {
    super(e), this.name = "CounterError";
  }
};
var O = class extends m {
  constructor() {
    super("Counter must be non-negative"), this.name = "CounterNegativeError";
  }
};
var T = class extends m {
  constructor() {
    super("Counter exceeds maximum safe integer value"), this.name = "CounterOverflowError";
  }
};
var b = class extends m {
  constructor() {
    super("Counter must be a finite integer"), this.name = "CounterNotIntegerError";
  }
};
var A = class extends i {
  constructor(e) {
    super(e), this.name = "TimeError";
  }
};
var C = class extends A {
  constructor() {
    super("Time must be non-negative"), this.name = "TimeNegativeError";
  }
};
var S = class extends A {
  constructor() {
    super("Time must be a finite number"), this.name = "TimeNotFiniteError";
  }
};
var B = class extends i {
  constructor(e) {
    super(e), this.name = "PeriodError";
  }
};
var w = class extends B {
  constructor(e) {
    super(`Period must be at least ${e} second(s)`), this.name = "PeriodTooSmallError";
  }
};
var _ = class extends B {
  constructor(e) {
    super(`Period must not exceed ${e} seconds`), this.name = "PeriodTooLargeError";
  }
};
var N = class extends i {
  constructor(e, t2) {
    super(e, t2), this.name = "CryptoError";
  }
};
var c = class extends N {
  constructor(e, t2) {
    super(`HMAC computation failed: ${e}`, t2), this.name = "HMACError";
  }
};
var v = class extends N {
  constructor(e, t2) {
    super(`Random byte generation failed: ${e}`, t2), this.name = "RandomBytesError";
  }
};
var G = class extends i {
  constructor(e) {
    super(e), this.name = "PluginError";
  }
};
var Y = class extends G {
  constructor() {
    super("Crypto plugin is required."), this.name = "CryptoPluginMissingError";
  }
};
var L = class extends G {
  constructor() {
    super("Base32 plugin is required."), this.name = "Base32PluginMissingError";
  }
};
var a = class extends i {
  constructor(e) {
    super(e), this.name = "ConfigurationError";
  }
};
var W = class extends a {
  constructor() {
    super("Secret is required. Use generateSecret() to create one, or provide via { secret: 'YOUR_BASE32_SECRET' }"), this.name = "SecretMissingError";
  }
};
var yr = new TextEncoder();
var xr = new TextDecoder();
var or = 16;
var sr = 64;
var ar = 1;
var ur = 3600;
var pr = Number.MAX_SAFE_INTEGER;
var lr = 99;
var er = /* @__PURE__ */ Symbol("otplib.guardrails.override");
function y(r2, e, t2) {
  if (typeof e != "number" || !Number.isSafeInteger(e)) throw new a(`Guardrail '${r2}' must be a safe integer`);
  if (e < t2) throw new a(`Guardrail '${r2}' must be >= ${t2}`);
}
var d = Object.freeze({ MIN_SECRET_BYTES: or, MAX_SECRET_BYTES: sr, MIN_PERIOD: ar, MAX_PERIOD: ur, MAX_COUNTER: pr, MAX_WINDOW: lr, [er]: false });
function hr(r2) {
  if (!r2) return d;
  r2.MIN_SECRET_BYTES !== void 0 && y("MIN_SECRET_BYTES", r2.MIN_SECRET_BYTES, 1), r2.MAX_SECRET_BYTES !== void 0 && y("MAX_SECRET_BYTES", r2.MAX_SECRET_BYTES, 1), r2.MIN_PERIOD !== void 0 && y("MIN_PERIOD", r2.MIN_PERIOD, 1), r2.MAX_PERIOD !== void 0 && y("MAX_PERIOD", r2.MAX_PERIOD, 1), r2.MAX_COUNTER !== void 0 && y("MAX_COUNTER", r2.MAX_COUNTER, 0), r2.MAX_WINDOW !== void 0 && y("MAX_WINDOW", r2.MAX_WINDOW, 1);
  let e = { ...d, ...r2 };
  if (e.MIN_SECRET_BYTES > e.MAX_SECRET_BYTES) throw new a("Guardrail 'MIN_SECRET_BYTES' must be <= 'MAX_SECRET_BYTES'");
  if (e.MIN_PERIOD > e.MAX_PERIOD) throw new a("Guardrail 'MIN_PERIOD' must be <= 'MAX_PERIOD'");
  return Object.freeze({ ...e, [er]: true });
}
function Or(r2, e = d) {
  if (r2.length < e.MIN_SECRET_BYTES) throw new h(e.MIN_SECRET_BYTES, r2.length);
  if (r2.length > e.MAX_SECRET_BYTES) throw new P(e.MAX_SECRET_BYTES, r2.length);
}
function br(r2, e = d) {
  if (typeof r2 == "number") {
    if (!Number.isFinite(r2) || !Number.isInteger(r2)) throw new b();
    if (!Number.isSafeInteger(r2)) throw new T();
  }
  let t2 = typeof r2 == "bigint" ? r2 : BigInt(r2);
  if (t2 < 0n) throw new O();
  if (t2 > BigInt(e.MAX_COUNTER)) throw new T();
}
function Ar(r2) {
  if (!Number.isFinite(r2)) throw new S();
  if (r2 < 0) throw new C();
}
function Cr(r2, e = d) {
  if (!Number.isInteger(r2) || r2 < e.MIN_PERIOD) throw new w(e.MIN_PERIOD);
  if (r2 > e.MAX_PERIOD) throw new _(e.MAX_PERIOD);
}
function _r(r2) {
  let e = typeof r2 == "bigint" ? r2 : BigInt(r2), t2 = new ArrayBuffer(8);
  return new DataView(t2).setBigUint64(0, e, false), new Uint8Array(t2);
}
function Rr(r2) {
  let e = r2[r2.length - 1] & 15;
  return (r2[e] & 127) << 24 | r2[e + 1] << 16 | r2[e + 2] << 8 | r2[e + 3];
}
function Ir(r2, e) {
  let t2 = 10 ** e;
  return (r2 % t2).toString().padStart(e, "0");
}
function gr(r2, e) {
  return r2.length === e.length;
}
function tr(r2, e) {
  let t2 = rr(r2), o = rr(e);
  if (!gr(t2, o)) return false;
  let n = 0;
  for (let s = 0; s < t2.length; s++) n |= t2[s] ^ o[s];
  return n === 0;
}
function rr(r2) {
  return typeof r2 == "string" ? yr.encode(r2) : r2;
}
function vr(r2, e) {
  return typeof r2 == "string" ? (nr(e), e.decode(r2)) : r2;
}
function dr(r2) {
  if (!r2) throw new Y();
}
function nr(r2) {
  if (!r2) throw new L();
}
function Xr(r2) {
  if (!r2) throw new W();
}
var K = class {
  constructor(e) {
    this.crypto = e;
  }
  get plugin() {
    return this.crypto;
  }
  async hmac(e, t2, o) {
    try {
      let n = this.crypto.hmac(e, t2, o);
      return n instanceof Promise ? await n : n;
    } catch (n) {
      let s = n instanceof Error ? n.message : String(n);
      throw new c(s, { cause: n });
    }
  }
  hmacSync(e, t2, o) {
    try {
      let n = this.crypto.hmac(e, t2, o);
      if (n instanceof Promise) throw new c("Crypto plugin does not support synchronous HMAC operations");
      return n;
    } catch (n) {
      if (n instanceof c) throw n;
      let s = n instanceof Error ? n.message : String(n);
      throw new c(s, { cause: n });
    }
  }
  randomBytes(e) {
    try {
      return this.crypto.randomBytes(e);
    } catch (t2) {
      let o = t2 instanceof Error ? t2.message : String(t2);
      throw new v(o, { cause: t2 });
    }
  }
};
function Wr(r2) {
  return new K(r2);
}

// node_modules/@otplib/hotp/dist/index.js
function v2(u) {
  let { secret: t2, counter: e, algorithm: r2 = "sha1", digits: i2 = 6, crypto: n, base32: o, guardrails: a2, hooks: s } = u;
  Xr(t2), dr(n);
  let c3 = vr(t2, o);
  Or(c3, a2), br(e, a2);
  let p = Wr(n), l2 = _r(e);
  return { ctx: p, algorithm: r2, digits: i2, secretBytes: c3, counterBytes: l2, hooks: s };
}
function F(u) {
  let { ctx: t2, algorithm: e, digits: r2, secretBytes: i2, counterBytes: n, hooks: o } = v2(u), a2 = t2.hmacSync(e, i2, n), s = o?.truncateDigest ? o.truncateDigest(a2) : Rr(a2);
  return o?.encodeToken ? o.encodeToken(s, r2) : Ir(s, r2);
}

// node_modules/@otplib/totp/dist/index.js
function k(r2) {
  let { secret: e, epoch: n = Math.floor(Date.now() / 1e3), t0: o = 0, period: i2 = 30, algorithm: s = "sha1", digits: l2 = 6, crypto: a2, base32: u, guardrails: c3 = hr(), hooks: t2 } = r2;
  Xr(e), dr(a2);
  let p = vr(e, u);
  Or(p, c3), Ar(n), Cr(i2, c3);
  let f = Math.floor((n - o) / i2);
  return { secret: p, counter: f, algorithm: s, digits: l2, crypto: a2, guardrails: c3, hooks: t2 };
}
function Z2(r2) {
  let e = k(r2);
  return F(e);
}

// node_modules/@scure/base/index.js
function isBytes(a2) {
  return a2 instanceof Uint8Array || ArrayBuffer.isView(a2) && a2.constructor.name === "Uint8Array" && "BYTES_PER_ELEMENT" in a2 && a2.BYTES_PER_ELEMENT === 1;
}
function isArrayOf(isString, arr) {
  if (!Array.isArray(arr))
    return false;
  if (arr.length === 0)
    return true;
  if (isString) {
    return arr.every((item) => typeof item === "string");
  } else {
    return arr.every((item) => Number.isSafeInteger(item));
  }
}
function astr(label, input) {
  if (typeof input !== "string")
    throw new TypeError(`${label}: string expected`);
  return true;
}
function anumber(n) {
  if (typeof n !== "number")
    throw new TypeError(`number expected, got ${typeof n}`);
  if (!Number.isSafeInteger(n))
    throw new RangeError(`invalid integer: ${n}`);
}
function aArr(input) {
  if (!Array.isArray(input))
    throw new TypeError("array expected");
}
function astrArr(label, input) {
  if (!isArrayOf(true, input))
    throw new TypeError(`${label}: array of strings expected`);
}
function anumArr(label, input) {
  if (!isArrayOf(false, input))
    throw new TypeError(`${label}: array of numbers expected`);
}
// @__NO_SIDE_EFFECTS__
function chain(...args) {
  const id = (a2) => a2;
  const wrap = (a2, b3) => (c3) => a2(b3(c3));
  const encode = args.map((x3) => x3.encode).reduceRight(wrap, id);
  const decode = args.map((x3) => x3.decode).reduce(wrap, id);
  return { encode, decode };
}
// @__NO_SIDE_EFFECTS__
function alphabet(letters) {
  const lettersA = typeof letters === "string" ? letters.split("") : letters;
  const len = lettersA.length;
  astrArr("alphabet", lettersA);
  const indexes = new Map(lettersA.map((l2, i2) => [l2, i2]));
  return {
    encode: (digits) => {
      aArr(digits);
      return digits.map((i2) => {
        if (!Number.isSafeInteger(i2) || i2 < 0 || i2 >= len)
          throw new Error(`alphabet.encode: digit index outside alphabet "${i2}". Allowed: ${letters}`);
        return lettersA[i2];
      });
    },
    decode: (input) => {
      aArr(input);
      return input.map((letter) => {
        astr("alphabet.decode", letter);
        const i2 = indexes.get(letter);
        if (i2 === void 0)
          throw new Error(`Unknown letter: "${letter}". Allowed: ${letters}`);
        return i2;
      });
    }
  };
}
// @__NO_SIDE_EFFECTS__
function join(separator = "") {
  astr("join", separator);
  return {
    encode: (from) => {
      astrArr("join.decode", from);
      return from.join(separator);
    },
    decode: (to) => {
      astr("join.decode", to);
      return to.split(separator);
    }
  };
}
// @__NO_SIDE_EFFECTS__
function padding(bits, chr = "=") {
  anumber(bits);
  astr("padding", chr);
  return {
    encode(data) {
      astrArr("padding.encode", data);
      while (data.length * bits % 8)
        data.push(chr);
      return data;
    },
    decode(input) {
      astrArr("padding.decode", input);
      let end = input.length;
      if (end * bits % 8)
        throw new Error("padding: invalid, string should have whole number of bytes");
      for (; end > 0 && input[end - 1] === chr; end--) {
        const last = end - 1;
        const byte = last * bits;
        if (byte % 8 === 0)
          throw new Error("padding: invalid, string has too much padding");
      }
      return input.slice(0, end);
    }
  };
}
var gcd = (a2, b3) => b3 === 0 ? a2 : gcd(b3, a2 % b3);
var radix2carry = /* @__NO_SIDE_EFFECTS__ */ (from, to) => from + (to - gcd(from, to));
var powers = /* @__PURE__ */ (() => {
  let res = [];
  for (let i2 = 0; i2 < 40; i2++)
    res.push(2 ** i2);
  return res;
})();
function convertRadix2(data, from, to, padding2) {
  aArr(data);
  if (from <= 0 || from > 32)
    throw new RangeError(`convertRadix2: wrong from=${from}`);
  if (to <= 0 || to > 32)
    throw new RangeError(`convertRadix2: wrong to=${to}`);
  if (/* @__PURE__ */ radix2carry(from, to) > 32) {
    throw new Error(`convertRadix2: carry overflow from=${from} to=${to} carryBits=${/* @__PURE__ */ radix2carry(from, to)}`);
  }
  let carry = 0;
  let pos = 0;
  const max = powers[from];
  const mask = powers[to] - 1;
  const res = [];
  for (const n of data) {
    anumber(n);
    if (n >= max)
      throw new Error(`convertRadix2: invalid data word=${n} from=${from}`);
    carry = carry << from | n;
    if (pos + from > 32)
      throw new Error(`convertRadix2: carry overflow pos=${pos} from=${from}`);
    pos += from;
    for (; pos >= to; pos -= to)
      res.push((carry >> pos - to & mask) >>> 0);
    const pow = powers[pos];
    if (pow === void 0)
      throw new Error("invalid carry");
    carry &= pow - 1;
  }
  carry = carry << to - pos & mask;
  if (!padding2 && pos >= from)
    throw new Error("Excess padding");
  if (!padding2 && carry > 0)
    throw new Error(`Non-zero padding: ${carry}`);
  if (padding2 && pos > 0)
    res.push(carry >>> 0);
  return res;
}
// @__NO_SIDE_EFFECTS__
function radix2(bits, revPadding = false) {
  anumber(bits);
  if (bits <= 0 || bits > 32)
    throw new RangeError("radix2: bits should be in (0..32]");
  if (/* @__PURE__ */ radix2carry(8, bits) > 32 || /* @__PURE__ */ radix2carry(bits, 8) > 32)
    throw new RangeError("radix2: carry overflow");
  return {
    encode: (bytes) => {
      if (!isBytes(bytes))
        throw new TypeError("radix2.encode input should be Uint8Array");
      return convertRadix2(Array.from(bytes), 8, bits, !revPadding);
    },
    decode: (digits) => {
      anumArr("radix2.decode", digits);
      return Uint8Array.from(convertRadix2(digits, bits, 8, revPadding));
    }
  };
}
var base32 = /* @__PURE__ */ Object.freeze(/* @__PURE__ */ chain(/* @__PURE__ */ radix2(5), /* @__PURE__ */ alphabet("ABCDEFGHIJKLMNOPQRSTUVWXYZ234567"), /* @__PURE__ */ padding(5), /* @__PURE__ */ join("")));

// node_modules/@otplib/plugin-base32-scure/dist/index.js
var r = class {
  name = "scure";
  encode(o, e = {}) {
    let { padding: t2 = false } = e, n = base32.encode(o);
    return t2 ? n : n.replace(/=+$/, "");
  }
  decode(o) {
    try {
      let e = o.toUpperCase(), t2 = e.padEnd(Math.ceil(e.length / 8) * 8, "=");
      return base32.decode(t2);
    } catch (e) {
      throw new Error(`Invalid Base32 string: ${e.message}`);
    }
  }
};
var d2 = Object.freeze(new r());

// node_modules/@otplib/plugin-crypto-noble/node_modules/@noble/hashes/utils.js
function isBytes2(a2) {
  return a2 instanceof Uint8Array || ArrayBuffer.isView(a2) && a2.constructor.name === "Uint8Array" && "BYTES_PER_ELEMENT" in a2 && a2.BYTES_PER_ELEMENT === 1;
}
function anumber2(n, title = "") {
  if (typeof n !== "number") {
    const prefix = title && `"${title}" `;
    throw new TypeError(`${prefix}expected number, got ${typeof n}`);
  }
  if (!Number.isSafeInteger(n) || n < 0) {
    const prefix = title && `"${title}" `;
    throw new RangeError(`${prefix}expected integer >= 0, got ${n}`);
  }
}
function abytes(value, length, title = "") {
  const bytes = isBytes2(value);
  const len = value?.length;
  const needsLen = length !== void 0;
  if (!bytes || needsLen && len !== length) {
    const prefix = title && `"${title}" `;
    const ofLen = needsLen ? ` of length ${length}` : "";
    const got = bytes ? `length=${len}` : `type=${typeof value}`;
    const message = prefix + "expected Uint8Array" + ofLen + ", got " + got;
    if (!bytes)
      throw new TypeError(message);
    throw new RangeError(message);
  }
  return value;
}
function ahash(h4) {
  if (typeof h4 !== "function" || typeof h4.create !== "function")
    throw new TypeError("Hash must wrapped by utils.createHasher");
  anumber2(h4.outputLen);
  anumber2(h4.blockLen);
  if (h4.outputLen < 1)
    throw new Error('"outputLen" must be >= 1');
  if (h4.blockLen < 1)
    throw new Error('"blockLen" must be >= 1');
}
function aexists(instance, checkFinished = true) {
  if (instance.destroyed)
    throw new Error("Hash instance has been destroyed");
  if (checkFinished && instance.finished)
    throw new Error("Hash#digest() has already been called");
}
function aoutput(out, instance) {
  abytes(out, void 0, "digestInto() output");
  const min = instance.outputLen;
  if (out.length < min) {
    throw new RangeError('"digestInto() output" expected to be of length >=' + min);
  }
}
function clean(...arrays) {
  for (let i2 = 0; i2 < arrays.length; i2++) {
    arrays[i2].fill(0);
  }
}
function createView(arr) {
  return new DataView(arr.buffer, arr.byteOffset, arr.byteLength);
}
function rotr(word, shift) {
  return word << 32 - shift | word >>> shift;
}
function rotl(word, shift) {
  return word << shift | word >>> 32 - shift >>> 0;
}
function createHasher(hashCons, info = {}) {
  const hashC = (msg, opts) => hashCons(opts).update(msg).digest();
  const tmp = hashCons(void 0);
  hashC.outputLen = tmp.outputLen;
  hashC.blockLen = tmp.blockLen;
  hashC.canXOF = tmp.canXOF;
  hashC.create = (opts) => hashCons(opts);
  Object.assign(hashC, info);
  return Object.freeze(hashC);
}
function randomBytes(bytesLength = 32) {
  anumber2(bytesLength, "bytesLength");
  const cr = typeof globalThis === "object" ? globalThis.crypto : null;
  if (typeof cr?.getRandomValues !== "function")
    throw new Error("crypto.getRandomValues must be defined");
  if (bytesLength > 65536)
    throw new RangeError(`"bytesLength" expected <= 65536, got ${bytesLength}`);
  return cr.getRandomValues(new Uint8Array(bytesLength));
}
var oidNist = (suffix) => ({
  // Current NIST hashAlgs suffixes used here fit in one DER subidentifier octet.
  // Larger suffix values would need base-128 OID encoding and a different length byte.
  oid: Uint8Array.from([6, 9, 96, 134, 72, 1, 101, 3, 4, 2, suffix])
});

// node_modules/@otplib/plugin-crypto-noble/node_modules/@noble/hashes/hmac.js
var _HMAC = class {
  oHash;
  iHash;
  blockLen;
  outputLen;
  canXOF = false;
  finished = false;
  destroyed = false;
  constructor(hash, key) {
    ahash(hash);
    abytes(key, void 0, "key");
    this.iHash = hash.create();
    if (typeof this.iHash.update !== "function")
      throw new Error("Expected instance of class which extends utils.Hash");
    this.blockLen = this.iHash.blockLen;
    this.outputLen = this.iHash.outputLen;
    const blockLen = this.blockLen;
    const pad = new Uint8Array(blockLen);
    pad.set(key.length > blockLen ? hash.create().update(key).digest() : key);
    for (let i2 = 0; i2 < pad.length; i2++)
      pad[i2] ^= 54;
    this.iHash.update(pad);
    this.oHash = hash.create();
    for (let i2 = 0; i2 < pad.length; i2++)
      pad[i2] ^= 54 ^ 92;
    this.oHash.update(pad);
    clean(pad);
  }
  update(buf) {
    aexists(this);
    this.iHash.update(buf);
    return this;
  }
  digestInto(out) {
    aexists(this);
    aoutput(out, this);
    this.finished = true;
    const buf = out.subarray(0, this.outputLen);
    this.iHash.digestInto(buf);
    this.oHash.update(buf);
    this.oHash.digestInto(buf);
    this.destroy();
  }
  digest() {
    const out = new Uint8Array(this.oHash.outputLen);
    this.digestInto(out);
    return out;
  }
  _cloneInto(to) {
    to ||= Object.create(Object.getPrototypeOf(this), {});
    const { oHash, iHash, finished, destroyed, blockLen, outputLen } = this;
    to = to;
    to.finished = finished;
    to.destroyed = destroyed;
    to.blockLen = blockLen;
    to.outputLen = outputLen;
    to.oHash = oHash._cloneInto(to.oHash);
    to.iHash = iHash._cloneInto(to.iHash);
    return to;
  }
  clone() {
    return this._cloneInto();
  }
  destroy() {
    this.destroyed = true;
    this.oHash.destroy();
    this.iHash.destroy();
  }
};
var hmac = /* @__PURE__ */ (() => {
  const hmac_ = ((hash, key, message) => new _HMAC(hash, key).update(message).digest());
  hmac_.create = (hash, key) => new _HMAC(hash, key);
  return hmac_;
})();

// node_modules/@otplib/plugin-crypto-noble/node_modules/@noble/hashes/_md.js
function Chi(a2, b3, c3) {
  return a2 & b3 ^ ~a2 & c3;
}
function Maj(a2, b3, c3) {
  return a2 & b3 ^ a2 & c3 ^ b3 & c3;
}
var HashMD = class {
  blockLen;
  outputLen;
  canXOF = false;
  padOffset;
  isLE;
  // For partial updates less than block size
  buffer;
  view;
  finished = false;
  length = 0;
  pos = 0;
  destroyed = false;
  constructor(blockLen, outputLen, padOffset, isLE) {
    this.blockLen = blockLen;
    this.outputLen = outputLen;
    this.padOffset = padOffset;
    this.isLE = isLE;
    this.buffer = new Uint8Array(blockLen);
    this.view = createView(this.buffer);
  }
  update(data) {
    aexists(this);
    abytes(data);
    const { view, buffer, blockLen } = this;
    const len = data.length;
    for (let pos = 0; pos < len; ) {
      const take = Math.min(blockLen - this.pos, len - pos);
      if (take === blockLen) {
        const dataView = createView(data);
        for (; blockLen <= len - pos; pos += blockLen)
          this.process(dataView, pos);
        continue;
      }
      buffer.set(data.subarray(pos, pos + take), this.pos);
      this.pos += take;
      pos += take;
      if (this.pos === blockLen) {
        this.process(view, 0);
        this.pos = 0;
      }
    }
    this.length += data.length;
    this.roundClean();
    return this;
  }
  digestInto(out) {
    aexists(this);
    aoutput(out, this);
    this.finished = true;
    const { buffer, view, blockLen, isLE } = this;
    let { pos } = this;
    buffer[pos++] = 128;
    clean(this.buffer.subarray(pos));
    if (this.padOffset > blockLen - pos) {
      this.process(view, 0);
      pos = 0;
    }
    for (let i2 = pos; i2 < blockLen; i2++)
      buffer[i2] = 0;
    view.setBigUint64(blockLen - 8, BigInt(this.length * 8), isLE);
    this.process(view, 0);
    const oview = createView(out);
    const len = this.outputLen;
    if (len % 4)
      throw new Error("_sha2: outputLen must be aligned to 32bit");
    const outLen = len / 4;
    const state = this.get();
    if (outLen > state.length)
      throw new Error("_sha2: outputLen bigger than state");
    for (let i2 = 0; i2 < outLen; i2++)
      oview.setUint32(4 * i2, state[i2], isLE);
  }
  digest() {
    const { buffer, outputLen } = this;
    this.digestInto(buffer);
    const res = buffer.slice(0, outputLen);
    this.destroy();
    return res;
  }
  _cloneInto(to) {
    to ||= new this.constructor();
    to.set(...this.get());
    const { blockLen, buffer, length, finished, destroyed, pos } = this;
    to.destroyed = destroyed;
    to.finished = finished;
    to.length = length;
    to.pos = pos;
    if (length % blockLen)
      to.buffer.set(buffer);
    return to;
  }
  clone() {
    return this._cloneInto();
  }
};
var SHA256_IV = /* @__PURE__ */ Uint32Array.from([
  1779033703,
  3144134277,
  1013904242,
  2773480762,
  1359893119,
  2600822924,
  528734635,
  1541459225
]);
var SHA512_IV = /* @__PURE__ */ Uint32Array.from([
  1779033703,
  4089235720,
  3144134277,
  2227873595,
  1013904242,
  4271175723,
  2773480762,
  1595750129,
  1359893119,
  2917565137,
  2600822924,
  725511199,
  528734635,
  4215389547,
  1541459225,
  327033209
]);

// node_modules/@otplib/plugin-crypto-noble/node_modules/@noble/hashes/legacy.js
var SHA1_IV = /* @__PURE__ */ Uint32Array.from([
  1732584193,
  4023233417,
  2562383102,
  271733878,
  3285377520
]);
var SHA1_W = /* @__PURE__ */ new Uint32Array(80);
var _SHA1 = class extends HashMD {
  A = SHA1_IV[0] | 0;
  B = SHA1_IV[1] | 0;
  C = SHA1_IV[2] | 0;
  D = SHA1_IV[3] | 0;
  E = SHA1_IV[4] | 0;
  constructor() {
    super(64, 20, 8, false);
  }
  get() {
    const { A: A3, B: B2, C: C2, D, E } = this;
    return [A3, B2, C2, D, E];
  }
  set(A3, B2, C2, D, E) {
    this.A = A3 | 0;
    this.B = B2 | 0;
    this.C = C2 | 0;
    this.D = D | 0;
    this.E = E | 0;
  }
  process(view, offset) {
    for (let i2 = 0; i2 < 16; i2++, offset += 4)
      SHA1_W[i2] = view.getUint32(offset, false);
    for (let i2 = 16; i2 < 80; i2++)
      SHA1_W[i2] = rotl(SHA1_W[i2 - 3] ^ SHA1_W[i2 - 8] ^ SHA1_W[i2 - 14] ^ SHA1_W[i2 - 16], 1);
    let { A: A3, B: B2, C: C2, D, E } = this;
    for (let i2 = 0; i2 < 80; i2++) {
      let F2, K2;
      if (i2 < 20) {
        F2 = Chi(B2, C2, D);
        K2 = 1518500249;
      } else if (i2 < 40) {
        F2 = B2 ^ C2 ^ D;
        K2 = 1859775393;
      } else if (i2 < 60) {
        F2 = Maj(B2, C2, D);
        K2 = 2400959708;
      } else {
        F2 = B2 ^ C2 ^ D;
        K2 = 3395469782;
      }
      const T2 = rotl(A3, 5) + F2 + E + K2 + SHA1_W[i2] | 0;
      E = D;
      D = C2;
      C2 = rotl(B2, 30);
      B2 = A3;
      A3 = T2;
    }
    A3 = A3 + this.A | 0;
    B2 = B2 + this.B | 0;
    C2 = C2 + this.C | 0;
    D = D + this.D | 0;
    E = E + this.E | 0;
    this.set(A3, B2, C2, D, E);
  }
  roundClean() {
    clean(SHA1_W);
  }
  destroy() {
    this.destroyed = true;
    this.set(0, 0, 0, 0, 0);
    clean(this.buffer);
  }
};
var sha1 = /* @__PURE__ */ createHasher(() => new _SHA1());

// node_modules/@otplib/plugin-crypto-noble/node_modules/@noble/hashes/_u64.js
var U32_MASK64 = /* @__PURE__ */ BigInt(2 ** 32 - 1);
var _32n = /* @__PURE__ */ BigInt(32);
function fromBig(n, le = false) {
  if (le)
    return { h: Number(n & U32_MASK64), l: Number(n >> _32n & U32_MASK64) };
  return { h: Number(n >> _32n & U32_MASK64) | 0, l: Number(n & U32_MASK64) | 0 };
}
function split(lst, le = false) {
  const len = lst.length;
  let Ah = new Uint32Array(len);
  let Al = new Uint32Array(len);
  for (let i2 = 0; i2 < len; i2++) {
    const { h: h4, l: l2 } = fromBig(lst[i2], le);
    [Ah[i2], Al[i2]] = [h4, l2];
  }
  return [Ah, Al];
}
var shrSH = (h4, _l, s) => h4 >>> s;
var shrSL = (h4, l2, s) => h4 << 32 - s | l2 >>> s;
var rotrSH = (h4, l2, s) => h4 >>> s | l2 << 32 - s;
var rotrSL = (h4, l2, s) => h4 << 32 - s | l2 >>> s;
var rotrBH = (h4, l2, s) => h4 << 64 - s | l2 >>> s - 32;
var rotrBL = (h4, l2, s) => h4 >>> s - 32 | l2 << 64 - s;
function add(Ah, Al, Bh, Bl) {
  const l2 = (Al >>> 0) + (Bl >>> 0);
  return { h: Ah + Bh + (l2 / 2 ** 32 | 0) | 0, l: l2 | 0 };
}
var add3L = (Al, Bl, Cl) => (Al >>> 0) + (Bl >>> 0) + (Cl >>> 0);
var add3H = (low, Ah, Bh, Ch) => Ah + Bh + Ch + (low / 2 ** 32 | 0) | 0;
var add4L = (Al, Bl, Cl, Dl) => (Al >>> 0) + (Bl >>> 0) + (Cl >>> 0) + (Dl >>> 0);
var add4H = (low, Ah, Bh, Ch, Dh) => Ah + Bh + Ch + Dh + (low / 2 ** 32 | 0) | 0;
var add5L = (Al, Bl, Cl, Dl, El) => (Al >>> 0) + (Bl >>> 0) + (Cl >>> 0) + (Dl >>> 0) + (El >>> 0);
var add5H = (low, Ah, Bh, Ch, Dh, Eh) => Ah + Bh + Ch + Dh + Eh + (low / 2 ** 32 | 0) | 0;

// node_modules/@otplib/plugin-crypto-noble/node_modules/@noble/hashes/sha2.js
var SHA256_K = /* @__PURE__ */ Uint32Array.from([
  1116352408,
  1899447441,
  3049323471,
  3921009573,
  961987163,
  1508970993,
  2453635748,
  2870763221,
  3624381080,
  310598401,
  607225278,
  1426881987,
  1925078388,
  2162078206,
  2614888103,
  3248222580,
  3835390401,
  4022224774,
  264347078,
  604807628,
  770255983,
  1249150122,
  1555081692,
  1996064986,
  2554220882,
  2821834349,
  2952996808,
  3210313671,
  3336571891,
  3584528711,
  113926993,
  338241895,
  666307205,
  773529912,
  1294757372,
  1396182291,
  1695183700,
  1986661051,
  2177026350,
  2456956037,
  2730485921,
  2820302411,
  3259730800,
  3345764771,
  3516065817,
  3600352804,
  4094571909,
  275423344,
  430227734,
  506948616,
  659060556,
  883997877,
  958139571,
  1322822218,
  1537002063,
  1747873779,
  1955562222,
  2024104815,
  2227730452,
  2361852424,
  2428436474,
  2756734187,
  3204031479,
  3329325298
]);
var SHA256_W = /* @__PURE__ */ new Uint32Array(64);
var SHA2_32B = class extends HashMD {
  constructor(outputLen) {
    super(64, outputLen, 8, false);
  }
  get() {
    const { A: A3, B: B2, C: C2, D, E, F: F2, G: G2, H } = this;
    return [A3, B2, C2, D, E, F2, G2, H];
  }
  // prettier-ignore
  set(A3, B2, C2, D, E, F2, G2, H) {
    this.A = A3 | 0;
    this.B = B2 | 0;
    this.C = C2 | 0;
    this.D = D | 0;
    this.E = E | 0;
    this.F = F2 | 0;
    this.G = G2 | 0;
    this.H = H | 0;
  }
  process(view, offset) {
    for (let i2 = 0; i2 < 16; i2++, offset += 4)
      SHA256_W[i2] = view.getUint32(offset, false);
    for (let i2 = 16; i2 < 64; i2++) {
      const W15 = SHA256_W[i2 - 15];
      const W2 = SHA256_W[i2 - 2];
      const s0 = rotr(W15, 7) ^ rotr(W15, 18) ^ W15 >>> 3;
      const s1 = rotr(W2, 17) ^ rotr(W2, 19) ^ W2 >>> 10;
      SHA256_W[i2] = s1 + SHA256_W[i2 - 7] + s0 + SHA256_W[i2 - 16] | 0;
    }
    let { A: A3, B: B2, C: C2, D, E, F: F2, G: G2, H } = this;
    for (let i2 = 0; i2 < 64; i2++) {
      const sigma1 = rotr(E, 6) ^ rotr(E, 11) ^ rotr(E, 25);
      const T1 = H + sigma1 + Chi(E, F2, G2) + SHA256_K[i2] + SHA256_W[i2] | 0;
      const sigma0 = rotr(A3, 2) ^ rotr(A3, 13) ^ rotr(A3, 22);
      const T2 = sigma0 + Maj(A3, B2, C2) | 0;
      H = G2;
      G2 = F2;
      F2 = E;
      E = D + T1 | 0;
      D = C2;
      C2 = B2;
      B2 = A3;
      A3 = T1 + T2 | 0;
    }
    A3 = A3 + this.A | 0;
    B2 = B2 + this.B | 0;
    C2 = C2 + this.C | 0;
    D = D + this.D | 0;
    E = E + this.E | 0;
    F2 = F2 + this.F | 0;
    G2 = G2 + this.G | 0;
    H = H + this.H | 0;
    this.set(A3, B2, C2, D, E, F2, G2, H);
  }
  roundClean() {
    clean(SHA256_W);
  }
  destroy() {
    this.destroyed = true;
    this.set(0, 0, 0, 0, 0, 0, 0, 0);
    clean(this.buffer);
  }
};
var _SHA256 = class extends SHA2_32B {
  // We cannot use array here since array allows indexing by variable
  // which means optimizer/compiler cannot use registers.
  A = SHA256_IV[0] | 0;
  B = SHA256_IV[1] | 0;
  C = SHA256_IV[2] | 0;
  D = SHA256_IV[3] | 0;
  E = SHA256_IV[4] | 0;
  F = SHA256_IV[5] | 0;
  G = SHA256_IV[6] | 0;
  H = SHA256_IV[7] | 0;
  constructor() {
    super(32);
  }
};
var K512 = /* @__PURE__ */ (() => split([
  "0x428a2f98d728ae22",
  "0x7137449123ef65cd",
  "0xb5c0fbcfec4d3b2f",
  "0xe9b5dba58189dbbc",
  "0x3956c25bf348b538",
  "0x59f111f1b605d019",
  "0x923f82a4af194f9b",
  "0xab1c5ed5da6d8118",
  "0xd807aa98a3030242",
  "0x12835b0145706fbe",
  "0x243185be4ee4b28c",
  "0x550c7dc3d5ffb4e2",
  "0x72be5d74f27b896f",
  "0x80deb1fe3b1696b1",
  "0x9bdc06a725c71235",
  "0xc19bf174cf692694",
  "0xe49b69c19ef14ad2",
  "0xefbe4786384f25e3",
  "0x0fc19dc68b8cd5b5",
  "0x240ca1cc77ac9c65",
  "0x2de92c6f592b0275",
  "0x4a7484aa6ea6e483",
  "0x5cb0a9dcbd41fbd4",
  "0x76f988da831153b5",
  "0x983e5152ee66dfab",
  "0xa831c66d2db43210",
  "0xb00327c898fb213f",
  "0xbf597fc7beef0ee4",
  "0xc6e00bf33da88fc2",
  "0xd5a79147930aa725",
  "0x06ca6351e003826f",
  "0x142929670a0e6e70",
  "0x27b70a8546d22ffc",
  "0x2e1b21385c26c926",
  "0x4d2c6dfc5ac42aed",
  "0x53380d139d95b3df",
  "0x650a73548baf63de",
  "0x766a0abb3c77b2a8",
  "0x81c2c92e47edaee6",
  "0x92722c851482353b",
  "0xa2bfe8a14cf10364",
  "0xa81a664bbc423001",
  "0xc24b8b70d0f89791",
  "0xc76c51a30654be30",
  "0xd192e819d6ef5218",
  "0xd69906245565a910",
  "0xf40e35855771202a",
  "0x106aa07032bbd1b8",
  "0x19a4c116b8d2d0c8",
  "0x1e376c085141ab53",
  "0x2748774cdf8eeb99",
  "0x34b0bcb5e19b48a8",
  "0x391c0cb3c5c95a63",
  "0x4ed8aa4ae3418acb",
  "0x5b9cca4f7763e373",
  "0x682e6ff3d6b2b8a3",
  "0x748f82ee5defb2fc",
  "0x78a5636f43172f60",
  "0x84c87814a1f0ab72",
  "0x8cc702081a6439ec",
  "0x90befffa23631e28",
  "0xa4506cebde82bde9",
  "0xbef9a3f7b2c67915",
  "0xc67178f2e372532b",
  "0xca273eceea26619c",
  "0xd186b8c721c0c207",
  "0xeada7dd6cde0eb1e",
  "0xf57d4f7fee6ed178",
  "0x06f067aa72176fba",
  "0x0a637dc5a2c898a6",
  "0x113f9804bef90dae",
  "0x1b710b35131c471b",
  "0x28db77f523047d84",
  "0x32caab7b40c72493",
  "0x3c9ebe0a15c9bebc",
  "0x431d67c49c100d4c",
  "0x4cc5d4becb3e42b6",
  "0x597f299cfc657e2a",
  "0x5fcb6fab3ad6faec",
  "0x6c44198c4a475817"
].map((n) => BigInt(n))))();
var SHA512_Kh = /* @__PURE__ */ (() => K512[0])();
var SHA512_Kl = /* @__PURE__ */ (() => K512[1])();
var SHA512_W_H = /* @__PURE__ */ new Uint32Array(80);
var SHA512_W_L = /* @__PURE__ */ new Uint32Array(80);
var SHA2_64B = class extends HashMD {
  constructor(outputLen) {
    super(128, outputLen, 16, false);
  }
  // prettier-ignore
  get() {
    const { Ah, Al, Bh, Bl, Ch, Cl, Dh, Dl, Eh, El, Fh, Fl, Gh, Gl, Hh, Hl } = this;
    return [Ah, Al, Bh, Bl, Ch, Cl, Dh, Dl, Eh, El, Fh, Fl, Gh, Gl, Hh, Hl];
  }
  // prettier-ignore
  set(Ah, Al, Bh, Bl, Ch, Cl, Dh, Dl, Eh, El, Fh, Fl, Gh, Gl, Hh, Hl) {
    this.Ah = Ah | 0;
    this.Al = Al | 0;
    this.Bh = Bh | 0;
    this.Bl = Bl | 0;
    this.Ch = Ch | 0;
    this.Cl = Cl | 0;
    this.Dh = Dh | 0;
    this.Dl = Dl | 0;
    this.Eh = Eh | 0;
    this.El = El | 0;
    this.Fh = Fh | 0;
    this.Fl = Fl | 0;
    this.Gh = Gh | 0;
    this.Gl = Gl | 0;
    this.Hh = Hh | 0;
    this.Hl = Hl | 0;
  }
  process(view, offset) {
    for (let i2 = 0; i2 < 16; i2++, offset += 4) {
      SHA512_W_H[i2] = view.getUint32(offset);
      SHA512_W_L[i2] = view.getUint32(offset += 4);
    }
    for (let i2 = 16; i2 < 80; i2++) {
      const W15h = SHA512_W_H[i2 - 15] | 0;
      const W15l = SHA512_W_L[i2 - 15] | 0;
      const s0h = rotrSH(W15h, W15l, 1) ^ rotrSH(W15h, W15l, 8) ^ shrSH(W15h, W15l, 7);
      const s0l = rotrSL(W15h, W15l, 1) ^ rotrSL(W15h, W15l, 8) ^ shrSL(W15h, W15l, 7);
      const W2h = SHA512_W_H[i2 - 2] | 0;
      const W2l = SHA512_W_L[i2 - 2] | 0;
      const s1h = rotrSH(W2h, W2l, 19) ^ rotrBH(W2h, W2l, 61) ^ shrSH(W2h, W2l, 6);
      const s1l = rotrSL(W2h, W2l, 19) ^ rotrBL(W2h, W2l, 61) ^ shrSL(W2h, W2l, 6);
      const SUMl = add4L(s0l, s1l, SHA512_W_L[i2 - 7], SHA512_W_L[i2 - 16]);
      const SUMh = add4H(SUMl, s0h, s1h, SHA512_W_H[i2 - 7], SHA512_W_H[i2 - 16]);
      SHA512_W_H[i2] = SUMh | 0;
      SHA512_W_L[i2] = SUMl | 0;
    }
    let { Ah, Al, Bh, Bl, Ch, Cl, Dh, Dl, Eh, El, Fh, Fl, Gh, Gl, Hh, Hl } = this;
    for (let i2 = 0; i2 < 80; i2++) {
      const sigma1h = rotrSH(Eh, El, 14) ^ rotrSH(Eh, El, 18) ^ rotrBH(Eh, El, 41);
      const sigma1l = rotrSL(Eh, El, 14) ^ rotrSL(Eh, El, 18) ^ rotrBL(Eh, El, 41);
      const CHIh = Eh & Fh ^ ~Eh & Gh;
      const CHIl = El & Fl ^ ~El & Gl;
      const T1ll = add5L(Hl, sigma1l, CHIl, SHA512_Kl[i2], SHA512_W_L[i2]);
      const T1h = add5H(T1ll, Hh, sigma1h, CHIh, SHA512_Kh[i2], SHA512_W_H[i2]);
      const T1l = T1ll | 0;
      const sigma0h = rotrSH(Ah, Al, 28) ^ rotrBH(Ah, Al, 34) ^ rotrBH(Ah, Al, 39);
      const sigma0l = rotrSL(Ah, Al, 28) ^ rotrBL(Ah, Al, 34) ^ rotrBL(Ah, Al, 39);
      const MAJh = Ah & Bh ^ Ah & Ch ^ Bh & Ch;
      const MAJl = Al & Bl ^ Al & Cl ^ Bl & Cl;
      Hh = Gh | 0;
      Hl = Gl | 0;
      Gh = Fh | 0;
      Gl = Fl | 0;
      Fh = Eh | 0;
      Fl = El | 0;
      ({ h: Eh, l: El } = add(Dh | 0, Dl | 0, T1h | 0, T1l | 0));
      Dh = Ch | 0;
      Dl = Cl | 0;
      Ch = Bh | 0;
      Cl = Bl | 0;
      Bh = Ah | 0;
      Bl = Al | 0;
      const All = add3L(T1l, sigma0l, MAJl);
      Ah = add3H(All, T1h, sigma0h, MAJh);
      Al = All | 0;
    }
    ({ h: Ah, l: Al } = add(this.Ah | 0, this.Al | 0, Ah | 0, Al | 0));
    ({ h: Bh, l: Bl } = add(this.Bh | 0, this.Bl | 0, Bh | 0, Bl | 0));
    ({ h: Ch, l: Cl } = add(this.Ch | 0, this.Cl | 0, Ch | 0, Cl | 0));
    ({ h: Dh, l: Dl } = add(this.Dh | 0, this.Dl | 0, Dh | 0, Dl | 0));
    ({ h: Eh, l: El } = add(this.Eh | 0, this.El | 0, Eh | 0, El | 0));
    ({ h: Fh, l: Fl } = add(this.Fh | 0, this.Fl | 0, Fh | 0, Fl | 0));
    ({ h: Gh, l: Gl } = add(this.Gh | 0, this.Gl | 0, Gh | 0, Gl | 0));
    ({ h: Hh, l: Hl } = add(this.Hh | 0, this.Hl | 0, Hh | 0, Hl | 0));
    this.set(Ah, Al, Bh, Bl, Ch, Cl, Dh, Dl, Eh, El, Fh, Fl, Gh, Gl, Hh, Hl);
  }
  roundClean() {
    clean(SHA512_W_H, SHA512_W_L);
  }
  destroy() {
    this.destroyed = true;
    clean(this.buffer);
    this.set(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
  }
};
var _SHA512 = class extends SHA2_64B {
  Ah = SHA512_IV[0] | 0;
  Al = SHA512_IV[1] | 0;
  Bh = SHA512_IV[2] | 0;
  Bl = SHA512_IV[3] | 0;
  Ch = SHA512_IV[4] | 0;
  Cl = SHA512_IV[5] | 0;
  Dh = SHA512_IV[6] | 0;
  Dl = SHA512_IV[7] | 0;
  Eh = SHA512_IV[8] | 0;
  El = SHA512_IV[9] | 0;
  Fh = SHA512_IV[10] | 0;
  Fl = SHA512_IV[11] | 0;
  Gh = SHA512_IV[12] | 0;
  Gl = SHA512_IV[13] | 0;
  Hh = SHA512_IV[14] | 0;
  Hl = SHA512_IV[15] | 0;
  constructor() {
    super(64);
  }
};
var sha256 = /* @__PURE__ */ createHasher(
  () => new _SHA256(),
  /* @__PURE__ */ oidNist(1)
);
var sha512 = /* @__PURE__ */ createHasher(
  () => new _SHA512(),
  /* @__PURE__ */ oidNist(3)
);

// node_modules/@otplib/plugin-crypto-noble/dist/index.js
var t = class {
  name = "noble";
  hmac(r2, n, a2) {
    return hmac(r2 === "sha1" ? sha1 : r2 === "sha256" ? sha256 : sha512, n, a2);
  }
  randomBytes(r2) {
    return randomBytes(r2);
  }
  constantTimeEqual(r2, n) {
    return tr(r2, n);
  }
};
var A2 = Object.freeze(new t());

// node_modules/otplib/dist/index.js
function c2(t2) {
  return { secret: t2.secret, strategy: t2.strategy ?? "totp", crypto: t2.crypto ?? A2, base32: t2.base32 ?? d2, algorithm: t2.algorithm ?? "sha1", digits: t2.digits ?? 6, period: t2.period ?? 30, epoch: t2.epoch ?? Math.floor(Date.now() / 1e3), t0: t2.t0 ?? 0, counter: t2.counter, guardrails: t2.guardrails ?? hr(), hooks: t2.hooks };
}
function l(t2, e, r2) {
  if (t2 === "totp") return r2.totp();
  if (t2 === "hotp") {
    if (e === void 0) throw new a("Counter is required for HOTP strategy. Example: { strategy: 'hotp', counter: 0 }");
    return r2.hotp(e);
  }
  throw new a(`Unknown OTP strategy: ${t2}. Valid strategies are 'totp' or 'hotp'.`);
}
function h3(t2) {
  let e = c2(t2), { secret: r2, crypto: a2, base32: o, algorithm: i2, digits: s } = e, n = { secret: r2, crypto: a2, base32: o, algorithm: i2, digits: s };
  return l(e.strategy, e.counter, { totp: () => Z2({ ...n, period: e.period, epoch: e.epoch, t0: e.t0, guardrails: e.guardrails }), hotp: (p) => F({ ...n, counter: p, guardrails: e.guardrails }) });
}

// playwright/render-invoice.ts
function requireEnv(name) {
  const value = process.env[name];
  if (!value) {
    throw new Error(`Missing required environment variable: ${name}`);
  }
  return value;
}
async function handleTwoFactorAuth(page) {
  if (page.url().includes("/showSetup")) {
    const secret = await page.inputValue("#secretInput");
    console.error(
      `First-time 2FA setup for this account \u2014 add this to .env to skip setup on future runs:
PLAYWRIGHT_TEST_TOTP_SECRET=${secret}`
    );
    await page.fill("#code", h3({ secret }));
    await page.click("#code-button");
    await page.waitForURL(
      (url) => !url.pathname.includes("/showSetup") && !url.pathname.includes("/verifySetup"),
      { waitUntil: "networkidle" }
    );
    return;
  }
  if (page.url().includes("/verifyLogin")) {
    const secret = requireEnv("PLAYWRIGHT_TEST_TOTP_SECRET");
    await page.fill("#code", h3({ secret }));
    await page.click("#code-button");
    await page.waitForURL((url) => !url.pathname.includes("/verifyLogin"), { waitUntil: "networkidle" });
  }
}
async function login(page, baseUrl, email, password) {
  await page.goto(`${baseUrl}/login`, { waitUntil: "networkidle" });
  await page.fill('input[name="Login[login]"]', email);
  await page.fill('input[name="Login[password]"]', password);
  await page.click("#login-button");
  await page.waitForURL((url) => !url.pathname.includes("/login"), { waitUntil: "networkidle" });
  await handleTwoFactorAuth(page);
}
async function main() {
  const invoiceId = process.argv[2];
  if (!invoiceId || !/^\d+$/.test(invoiceId)) {
    console.error("Usage: node render-invoice.js <invoice-id> [output-path]");
    process.exit(1);
  }
  const outputPath = process.argv[3] ?? `playwright/output/invoice-${invoiceId}.pdf`;
  const baseUrl = process.env.BASE_URL || "http://localhost";
  const email = requireEnv("PLAYWRIGHT_TEST_EMAIL");
  const password = requireEnv("PLAYWRIGHT_TEST_PASSWORD");
  const browser = await chromium.launch();
  try {
    const page = await browser.newPage();
    await login(page, baseUrl, email, password);
    const response = await page.goto(`${baseUrl}/invoice/inv/pdfPlaywrightDocument/${invoiceId}`, { waitUntil: "networkidle" });
    if (!response || !response.ok()) {
      throw new Error(
        `Failed to load invoice ${invoiceId}: HTTP ${response?.status() ?? "no response"} at ${page.url()} \u2014 check that PLAYWRIGHT_TEST_EMAIL is admin, or owns/is linked to this invoice as an observer.`
      );
    }
    await page.emulateMedia({ media: "screen" });
    await page.pdf({
      path: outputPath,
      format: "A4",
      printBackground: true,
      margin: { left: "15mm", right: "15mm", top: "16mm", bottom: "16mm" }
    });
    console.log(`PDF written to ${outputPath}`);
  } finally {
    await browser.close();
  }
}
main().catch((error) => {
  console.error(error);
  process.exit(1);
});
/*! Bundled license information:

@scure/base/index.js:
  (*! scure-base - MIT License (c) 2022 Paul Miller (paulmillr.com) *)
*/
