const config = {
  plugins: {
    "@tailwindcss/postcss": {},
    // 把 Tailwind v4 产出的 @layer 拍平成普通 CSS，兼容微信 web-view 等
    // 不支持 CSS cascade layers 的老内核（否则整块样式被丢弃 → 页面无样式）
    "@csstools/postcss-cascade-layers": {},
  },
};

export default config;
