-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 16-03-2025 a las 01:49:09
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `polleria`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_pedidos`
--

CREATE TABLE `detalles_pedidos` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) DEFAULT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `cantidad` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalles_pedidos`
--

INSERT INTO `detalles_pedidos` (`id`, `pedido_id`, `producto_id`, `cantidad`) VALUES
(390, 191, 24, 3),
(391, 192, 1, 1),
(392, 192, 15, 3),
(393, 192, 19, 1),
(394, 193, 20, 2),
(395, 193, 21, 2),
(396, 194, 1, 1),
(397, 195, 1, 5),
(398, 196, 1, 2),
(399, 196, 15, 2),
(400, 196, 19, 2),
(401, 194, 15, 0),
(402, 194, 19, 1),
(403, 194, 24, 1),
(404, 197, 1, 1),
(405, 197, 15, 0),
(406, 197, 24, 1),
(407, 198, 1, 1),
(408, 198, 15, 2),
(409, 198, 22, 1),
(410, 198, 24, 1),
(411, 199, 24, 0),
(412, 200, 1, 1),
(413, 200, 15, 1),
(414, 201, 1, 1),
(415, 201, 15, 1),
(416, 201, 19, 2),
(417, 201, 24, 1),
(418, 202, 15, 1),
(419, 202, 19, 1),
(420, 202, 20, 2),
(421, 203, 1, 4),
(422, 203, 24, 4),
(423, 204, 1, 1),
(424, 204, 15, 1),
(425, 204, 19, 1),
(426, 205, 1, 1),
(427, 205, 15, 1),
(428, 205, 19, 1),
(429, 206, 1, 1),
(430, 206, 15, 1),
(431, 206, 19, 1),
(432, 207, 1, 1),
(433, 207, 15, 1),
(434, 208, 1, 1),
(435, 208, 15, 1),
(436, 208, 24, 1),
(437, 209, 1, 1),
(438, 209, 19, 1),
(439, 209, 23, 1),
(440, 209, 24, 1),
(441, 210, NULL, 1),
(442, 210, NULL, 1),
(443, 210, NULL, 1),
(444, 210, 1, 1),
(445, 210, 15, 1),
(446, 210, 19, 1),
(447, 210, 20, 3),
(448, 210, 24, 1),
(449, 210, 23, 4),
(450, 210, 24, 5),
(451, 211, 1, 1),
(452, 211, 15, 1),
(453, 211, 19, 3),
(454, 212, 1, 1),
(455, 212, 15, 1),
(456, 212, 19, 1),
(457, 213, 15, 1),
(458, 213, 19, 1),
(459, 213, 22, 1),
(460, 213, 24, 1),
(461, 214, 1, 1),
(462, 214, 15, 1),
(463, 214, 19, 1),
(464, 215, 1, 3),
(465, 215, 15, 2),
(466, 215, 20, 2),
(467, 215, 24, 2),
(468, 215, 20, 1),
(469, 215, 21, 1),
(470, 215, 24, 1),
(471, 216, 1, 2),
(472, 216, 24, 1),
(473, 217, 1, 1),
(474, 217, 15, 1),
(475, 217, 19, 1),
(476, 218, 1, 1),
(477, 218, 15, 1),
(478, 218, 19, 3),
(479, 219, 1, 1),
(480, 219, 20, 1),
(481, 219, 24, 1),
(482, 220, 1, 1),
(483, 220, 15, 1),
(484, 220, 19, 1),
(485, 221, 1, 1),
(486, 221, 15, 1),
(487, 221, 19, 1),
(488, 221, 22, 3),
(489, 222, 1, 1),
(490, 222, 15, 1),
(491, 222, 19, 1),
(492, 223, 20, 1),
(493, 223, 21, 1),
(494, 223, 22, 1),
(495, 223, 23, 1),
(496, 224, 21, 1),
(497, 224, 24, 2),
(498, 225, 1, 1),
(499, 225, 15, 1),
(500, 225, 19, 1),
(501, 226, 1, 1),
(502, 226, 15, 1),
(503, 226, 19, 1),
(504, 227, 1, 1),
(505, 227, 15, 1),
(506, 227, 19, 1),
(507, 228, 1, 1),
(508, 228, 15, 1),
(509, 228, 19, 1),
(510, 229, 20, 1),
(511, 229, 22, 1),
(512, 229, 24, 1),
(513, 230, 1, 2),
(514, 230, 15, 2),
(515, 230, 24, 1),
(516, 231, 1, 1),
(517, 231, 15, 1),
(518, 231, 19, 1),
(519, 231, 24, 4),
(520, 232, 1, 1),
(521, 232, 15, 1),
(522, 232, 19, 3),
(523, 233, 1, 2),
(524, 233, 15, 2),
(525, 233, 24, 2),
(526, 234, 1, 1),
(527, 234, 15, 1),
(528, 234, 19, 1),
(529, 235, 1, 1),
(530, 235, 15, 4),
(531, 235, 19, 1),
(532, 236, 1, 1),
(533, 236, 15, 1),
(534, 237, 1, 1),
(535, 237, 15, 1),
(536, 237, 19, 1),
(537, 238, 1, 1),
(538, 238, 15, 1),
(539, 234, 1, 1),
(540, 234, 15, 1),
(541, 234, 19, 1),
(542, 234, 24, 1),
(543, 232, 19, 3),
(544, 232, 20, 1),
(545, 232, 22, 1),
(546, 239, 1, 1),
(547, 239, 15, 1),
(548, 239, 19, 1),
(549, 239, 22, 1),
(550, 239, 24, 1),
(551, 240, 1, 1),
(552, 240, 15, 1),
(553, 240, 19, 1),
(554, 240, 22, 3),
(555, 241, 1, 1),
(556, 241, 15, 1),
(557, 241, 19, 1),
(558, 241, 20, 1),
(559, 241, 24, 1),
(560, 242, 1, 1),
(561, 242, 15, 1),
(562, 242, 19, 1),
(563, 242, 1, 3),
(564, 242, 15, 1),
(565, 242, 19, 1),
(566, 242, 24, 1),
(567, 243, 1, 1),
(568, 243, 15, 1),
(569, 243, 19, 1),
(570, 243, 24, 3),
(571, 244, 1, 1),
(572, 244, 15, 1),
(573, 244, 19, 1),
(574, 245, 1, 1),
(575, 245, 15, 1),
(576, 245, 19, 1),
(577, 246, 1, 1),
(578, 246, 15, 1),
(579, 246, 19, 1),
(580, 247, 1, 2),
(581, 247, 19, 2),
(582, 247, 22, 2),
(583, 247, 24, 2),
(584, 248, 1, 1),
(585, 248, 15, 1),
(586, 248, 24, 1),
(589, 249, 24, 1),
(590, 250, 24, 4),
(591, 251, 1, 1),
(592, 251, 15, 1),
(593, 251, 24, 1),
(594, 252, 1, 1),
(595, 252, 15, 1),
(597, 253, 24, 5),
(598, 254, 1, 1),
(599, 254, 15, 1),
(600, 254, 19, 1),
(601, 254, 24, 1),
(602, 250, 1, 1),
(603, 250, 15, 1),
(604, 250, 19, 1),
(605, 255, 1, 2),
(606, 255, 15, 1),
(607, 255, 19, 3),
(610, 256, 19, 1),
(611, 249, 1, 1),
(612, 249, 15, 3),
(613, 249, 24, 2),
(614, 257, 1, 1),
(615, 257, 15, 1),
(616, 257, 19, 2),
(617, 257, 24, 3),
(618, 258, 1, 1),
(619, 258, 15, 1),
(620, 258, 20, 1),
(621, 258, 24, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mesas`
--

CREATE TABLE `mesas` (
  `id` int(11) NOT NULL,
  `numero` int(11) DEFAULT NULL,
  `estado` enum('ocupada','disponible') NOT NULL DEFAULT 'disponible',
  `letra` char(1) DEFAULT NULL CHECK (`letra` regexp '^[A-Za-z]$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mesas`
--

INSERT INTO `mesas` (`id`, `numero`, `estado`, `letra`) VALUES
(1, 1, 'ocupada', NULL),
(2, 2, 'disponible', NULL),
(3, 3, 'disponible', NULL),
(4, 4, 'disponible', NULL),
(5, 5, 'disponible', NULL),
(6, 6, 'disponible', NULL),
(7, 7, 'disponible', NULL),
(8, 8, 'disponible', NULL),
(9, 9, 'disponible', NULL),
(10, 10, 'disponible', NULL),
(11, 11, 'disponible', NULL),
(12, 12, 'disponible', NULL),
(13, 13, 'disponible', NULL),
(14, 14, 'disponible', NULL),
(15, 15, 'disponible', NULL),
(16, 16, 'disponible', NULL),
(17, 17, 'disponible', NULL),
(18, 18, 'disponible', NULL),
(19, 19, 'disponible', NULL),
(20, 20, 'disponible', NULL),
(21, 21, 'disponible', NULL),
(26, NULL, 'ocupada', 'A'),
(27, NULL, 'ocupada', 'B'),
(28, NULL, 'disponible', 'C'),
(29, NULL, 'disponible', 'D'),
(30, NULL, 'disponible', 'E'),
(31, NULL, 'disponible', 'F'),
(32, NULL, 'disponible', 'G'),
(33, NULL, 'disponible', 'H'),
(34, NULL, 'disponible', 'G');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `mesa_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `nombre_cliente` varchar(50) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `estado` enum('en_proceso','finalizado','anulado') DEFAULT 'en_proceso'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `mesa_id`, `usuario_id`, `fecha`, `nombre_cliente`, `direccion`, `estado`) VALUES
(191, 1, 3, '2025-01-09 13:39:47', NULL, NULL, 'finalizado'),
(192, 2, 43, '2025-01-09 13:41:36', NULL, NULL, 'finalizado'),
(193, 3, 2, '2025-01-09 13:42:58', NULL, NULL, 'finalizado'),
(194, 26, 3, '2025-01-09 13:47:55', 'aaaaaaaa', 'bbbbbbbbb', 'anulado'),
(195, 27, 43, '2025-01-09 13:49:03', 'loquito yoni', 'Avenida te las tragas', 'finalizado'),
(196, 28, 2, '2025-01-09 13:50:18', 'Sonia Morales', 'kakakauu', 'finalizado'),
(197, 27, 3, '2025-01-09 15:13:37', 'Liam Franzua', 'Aveni, nola manoma', 'anulado'),
(198, 1, 3, '2025-01-09 16:31:03', NULL, NULL, 'anulado'),
(199, 4, 3, '2025-01-09 16:33:01', NULL, NULL, 'finalizado'),
(200, 6, 3, '2025-01-09 16:34:07', NULL, NULL, 'anulado'),
(201, 1, 3, '2025-01-09 17:18:56', NULL, NULL, 'finalizado'),
(202, 28, 3, '2025-01-09 17:19:20', 'Liam Cabezon', 'Av. Malecon Rimac #994', 'finalizado'),
(203, 27, 3, '2025-01-10 17:18:09', 'jose jose', 'kiko porton 897', 'anulado'),
(204, 26, 3, '2025-01-10 17:22:11', '', '', 'anulado'),
(205, 26, 3, '2025-01-10 17:24:47', 'jose fina', 'finolissssssss', 'anulado'),
(206, 1, 3, '2025-01-10 17:31:25', NULL, NULL, 'anulado'),
(207, 1, 43, '2025-01-10 18:02:48', NULL, NULL, 'anulado'),
(208, 26, 43, '2025-01-10 18:40:42', 'luchin', 'perez', 'anulado'),
(209, 26, 3, '2025-02-21 18:59:15', 'juancito pepe', 'gaaaaaaaaaaaaaaaaaaaaaaaa', 'finalizado'),
(210, 1, 3, '2025-02-21 19:43:05', NULL, NULL, 'finalizado'),
(211, 27, 3, '2025-03-08 11:18:52', 'Juana la loca', 'su casa dice', 'finalizado'),
(212, 27, 44, '2025-03-08 11:20:54', '', '', 'finalizado'),
(213, 5, 44, '2025-03-08 11:26:51', NULL, NULL, 'finalizado'),
(214, 2, 44, '2025-03-08 11:39:31', NULL, NULL, 'finalizado'),
(215, 26, 3, '2025-03-08 11:43:21', 'Luis Fonsi', 'Despacito 764', 'finalizado'),
(216, 26, 44, '2025-03-08 12:36:16', 'Claderoni', 'Av. palmeiras', 'finalizado'),
(217, 1, 44, '2025-03-08 12:47:04', NULL, NULL, 'finalizado'),
(218, 14, 44, '2025-03-08 12:51:45', NULL, NULL, 'finalizado'),
(219, 2, 44, '2025-03-08 14:06:26', NULL, NULL, 'finalizado'),
(220, 1, 44, '2025-03-08 14:56:16', NULL, NULL, 'finalizado'),
(221, 2, 44, '2025-03-08 14:57:34', NULL, NULL, 'finalizado'),
(222, 26, 44, '2025-03-08 14:58:28', 'JeanPierre Fajardo Nano', 'Av. Malecon Rimac #994', 'finalizado'),
(223, 27, 44, '2025-03-08 14:59:10', 'Jose Miguel', 'New York', 'finalizado'),
(224, 32, 44, '2025-03-08 15:00:52', 'El chuchuri flex', 'Asociacion las Lomas de Santo Domingo, AV. Principal Los Lirios Mnza V Lote 1 - Santa Rosa', 'finalizado'),
(225, 32, 44, '2025-03-08 15:01:29', 'Jose Miguel', 'Av. Malecon Rimac #994', 'finalizado'),
(226, 1, 2, '2025-03-08 15:14:36', NULL, NULL, 'finalizado'),
(227, 2, 2, '2025-03-08 15:14:45', NULL, NULL, 'finalizado'),
(228, 29, 2, '2025-03-08 15:15:12', 'Jose Miguel', 'Av. Malecon Rimac #994', 'finalizado'),
(229, 32, 2, '2025-03-08 15:15:24', 'Jose Miguel', 'Av. Malecon Rimac #994', 'finalizado'),
(230, 1, 3, '2025-03-08 18:50:11', NULL, NULL, 'anulado'),
(231, 2, 3, '2025-03-08 18:50:37', NULL, NULL, 'finalizado'),
(232, 1, 3, '2025-03-08 18:52:54', NULL, NULL, 'finalizado'),
(233, 2, 2, '2025-03-08 19:05:04', NULL, NULL, 'finalizado'),
(234, 3, 2, '2025-03-08 19:07:07', NULL, NULL, 'finalizado'),
(235, 26, 3, '2025-03-08 19:13:59', 'Jose Miguel', 'Av. Malecon Rimac #994', 'finalizado'),
(236, 28, 2, '2025-03-08 19:39:33', 'JeanPierre Fajardo Nano', 'Av. Malecon Rimac #994', 'finalizado'),
(237, 28, 2, '2025-03-08 19:59:05', 'dawdawd', 'Av. Malecon Rimac #994', 'finalizado'),
(238, 32, 2, '2025-03-08 20:06:09', 'Jose Miguel', 'Av. Malecon Rimac #994', 'finalizado'),
(239, 1, 3, '2025-03-08 21:55:42', NULL, NULL, 'finalizado'),
(240, 11, 3, '2025-03-08 22:01:08', NULL, NULL, 'finalizado'),
(241, 1, 3, '2025-03-11 12:44:13', NULL, NULL, 'finalizado'),
(242, 26, 3, '2025-03-11 20:07:26', 'Ivan David Demonio', 'Las Tinieblas 666', 'finalizado'),
(243, 2, 2, '2025-03-11 22:18:55', NULL, NULL, 'finalizado'),
(244, 2, 2, '2025-03-11 22:23:02', NULL, NULL, 'finalizado'),
(245, 2, 2, '2025-03-11 22:35:16', NULL, NULL, 'finalizado'),
(246, 2, 2, '2025-03-11 22:41:56', NULL, NULL, 'finalizado'),
(247, 27, 2, '2025-03-11 23:20:59', 'Jose Miguel', 'New York', 'finalizado'),
(248, 1, 44, '2025-03-12 00:18:03', NULL, NULL, 'finalizado'),
(249, 26, 44, '2025-03-12 00:49:25', 'JeanPierre Fajardo Nano', 'New York', 'en_proceso'),
(250, 1, 44, '2025-03-12 00:54:57', NULL, NULL, 'anulado'),
(251, 3, 3, '2025-03-12 00:59:03', NULL, NULL, 'finalizado'),
(252, 2, 2, '2025-03-12 01:04:52', NULL, NULL, 'anulado'),
(253, 11, 2, '2025-03-12 01:05:04', NULL, NULL, 'anulado'),
(254, 27, 2, '2025-03-12 01:06:52', 'dawdawd', 'New York', 'anulado'),
(255, 1, 3, '2025-03-12 13:23:35', NULL, NULL, 'anulado'),
(256, 1, 3, '2025-03-13 18:46:05', NULL, NULL, 'en_proceso'),
(257, 27, 44, '2025-03-13 19:15:52', 'JeanPierre Fajardo Nano', 'New York', 'anulado'),
(258, 27, 44, '2025-03-13 19:19:38', 'Luis Miguel', 'Av. Malecon Rimac #994', 'en_proceso');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos_anulados`
--

CREATE TABLE `pedidos_anulados` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `cantidad_anulada` int(11) DEFAULT NULL,
  `motivo` text NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `fecha_anulacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos_anulados`
--

INSERT INTO `pedidos_anulados` (`id`, `pedido_id`, `producto_id`, `cantidad_anulada`, `motivo`, `usuario_id`, `fecha_anulacion`) VALUES
(1, 194, 15, 1, 'ya no quieren', NULL, '2025-01-09 15:12:41'),
(2, 197, NULL, NULL, 'ya se van', NULL, '2025-01-09 15:14:30'),
(3, 197, 15, 1, 'ya se van', NULL, '2025-01-09 15:24:04'),
(4, 197, NULL, NULL, 'ya se van', NULL, '2025-01-09 15:24:36'),
(5, 197, NULL, NULL, 'ya se van', NULL, '2025-01-09 15:38:26'),
(6, 194, NULL, NULL, 'ya no quieren', NULL, '2025-01-09 15:52:11'),
(7, 198, 15, 1, 'ya no quieren', NULL, '2025-01-09 16:31:15'),
(8, 198, NULL, NULL, 'ya se van', NULL, '2025-01-09 16:31:43'),
(9, 199, 24, 1, 'ya no quieren', NULL, '2025-01-09 16:33:16'),
(10, 199, 24, 2, 'ya no quieren', NULL, '2025-01-09 16:33:27'),
(11, 200, NULL, NULL, 'ya no quieren', NULL, '2025-01-09 16:34:16'),
(12, 203, 1, 2, 'ya se van', NULL, '2025-01-10 17:19:02'),
(13, 203, 24, 2, 'ya se van', NULL, '2025-01-10 17:19:17'),
(14, 203, NULL, NULL, 'ya se van', NULL, '2025-01-10 17:21:32'),
(15, 204, NULL, NULL, 'ya se van', NULL, '2025-01-10 17:22:16'),
(16, 205, NULL, NULL, 'ya se van', NULL, '2025-01-10 17:24:52'),
(17, 206, NULL, NULL, 'ya se van', NULL, '2025-01-10 17:31:44'),
(18, 207, NULL, NULL, 'una nena liam', NULL, '2025-01-10 18:58:31'),
(19, 208, NULL, NULL, 'ya se van', NULL, '2025-01-10 18:58:40'),
(20, 230, NULL, NULL, 'ya se van', NULL, '2025-03-08 18:50:19'),
(21, 250, 24, 7, 'una nena liam', NULL, '2025-03-12 11:02:16'),
(22, 250, NULL, NULL, 'ya se van', NULL, '2025-03-12 11:34:21'),
(23, 252, 19, 1, 'ESTA REFEO', NULL, '2025-03-12 13:02:23'),
(24, 252, NULL, NULL, 'ya no quieren', NULL, '2025-03-12 13:02:59'),
(25, 253, NULL, NULL, 'ya no quieren', NULL, '2025-03-12 13:03:50'),
(26, 255, 15, 1, 'ESTA REFEO', NULL, '2025-03-13 14:37:56'),
(27, 255, 19, 2, 'una nena liam', NULL, '2025-03-13 14:38:08'),
(28, 255, 1, 1, 'una nena liam', 44, '2025-03-13 16:45:25'),
(29, 255, NULL, NULL, 'ya no quieren', 44, '2025-03-13 16:46:30'),
(30, 254, NULL, NULL, 'ya no quieren', NULL, '2025-03-13 17:20:52'),
(31, 256, 1, 1, 'ESTA REFEO', 3, '2025-03-13 19:07:31'),
(32, 256, 15, 1, 'ya se van', 3, '2025-03-13 19:07:48'),
(33, 249, 1, 1, 'ya se van', 44, '2025-03-13 19:08:33'),
(34, 249, 15, 1, 'ya se van', 44, '2025-03-13 19:09:36'),
(35, 257, 15, 1, 'ESTA REFEO', NULL, '2025-03-13 19:16:06'),
(36, 257, NULL, NULL, 'ya no quieren', 44, '2025-03-13 19:16:38'),
(37, 258, 1, 1, 'gokuuuuuu', 44, '2025-03-13 19:19:52'),
(38, 258, 15, 2, 'gokuuuuuu', 44, '2025-03-13 19:20:04'),
(39, 258, 20, 1, 'gokuuuuuu', 44, '2025-03-13 19:20:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `nombre`, `precio`, `categoria`, `descripcion`) VALUES
(1, 'Combo Loco 1', 49.99, 'COMBOS LOCOS', 'Pollo entero, Chaufa, Papas fritas, ensalada, cremas, Gaseosa 1.5 Lt.'),
(15, 'Combo Loco 2', 49.99, 'COMBOS LOCOS', 'ajiao, pollo flito'),
(19, 'Pollo entero + Gaseosa 1.5Lt', 39.99, 'COMBOS LOCOS', 'papas fritas, ensalada, cremas'),
(20, 'Arroz Salvaje', 15.00, 'OTROS GUSTITOS', 'chaufita con toda la tecnica'),
(21, 'Conbinado', 17.00, 'OTROS GUSTITOS', 'tallarines. frejoles y helado'),
(22, 'Antitcuchos', 15.00, 'PARRILLAS', 'corazoncito de tu ex'),
(23, 'Chuleta', 15.99, 'PARRILLAS', 'papas fritas y arroz'),
(24, 'Pollo entero + Chuleta', 58.00, 'COMBO CALENTON FAMILIAR', '1 palito de anticucho, palito de hogdog, papas fritas, ensalada, cremas, gaseosa 1.5 Lt');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes`
--

CREATE TABLE `reportes` (
  `id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `ventas_totales` decimal(10,2) NOT NULL,
  `cantidad_ventas` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`) VALUES
(1, 'Administrador'),
(2, 'Mozo'),
(3, 'Cajero');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) DEFAULT NULL,
  `tipo` enum('venta','pre-venta','boleta','factura') NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol_id` int(11) DEFAULT NULL,
  `documento` varchar(20) NOT NULL,
  `fecha_creacion` date DEFAULT curdate(),
  `estado` enum('Activo','Inactivo') NOT NULL DEFAULT 'Activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `username`, `password`, `rol_id`, `documento`, `fecha_creacion`, `estado`) VALUES
(2, 'Ivan', 'David Tavara ', '76417141', '123456', 2, '76417141', '2024-10-15', 'Activo'),
(3, 'luis', 'Albert', '76548541', '123456', 1, '76548541', '2024-10-15', 'Activo'),
(43, 'Alfred', 'Batman Chero', '88888888', '1234', 3, '88888888', '2024-10-15', 'Activo'),
(44, 'Juan', 'Chin Perez', '99999999', '111111', 3, '99999999', '2008-02-24', 'Activo');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `detalles_pedidos`
--
ALTER TABLE `detalles_pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `mesas`
--
ALTER TABLE `mesas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mesa_id` (`mesa_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `pedidos_anulados`
--
ALTER TABLE `pedidos_anulados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pedido_anulado_pedido` (`pedido_id`),
  ADD KEY `fk_pedido_anulado_producto` (`producto_id`),
  ADD KEY `fk_pedido_anulado_usuario` (`usuario_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `rol_id` (`rol_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `detalles_pedidos`
--
ALTER TABLE `detalles_pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=622;

--
-- AUTO_INCREMENT de la tabla `mesas`
--
ALTER TABLE `mesas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=259;

--
-- AUTO_INCREMENT de la tabla `pedidos_anulados`
--
ALTER TABLE `pedidos_anulados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `reportes`
--
ALTER TABLE `reportes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalles_pedidos`
--
ALTER TABLE `detalles_pedidos`
  ADD CONSTRAINT `detalles_pedidos_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  ADD CONSTRAINT `detalles_pedidos_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`mesa_id`) REFERENCES `mesas` (`id`),
  ADD CONSTRAINT `pedidos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `pedidos_anulados`
--
ALTER TABLE `pedidos_anulados`
  ADD CONSTRAINT `fk_pedido_anulado_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  ADD CONSTRAINT `fk_pedido_anulado_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`),
  ADD CONSTRAINT `fk_pedido_anulado_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
