# Our long/lat postcodes table
DROP TABLE IF EXISTS `postcode_zone`;
CREATE TABLE `postcode_zone`
(
    `postcode`        varchar(15)  NOT NULL,
    `postcode_lookup` varchar(15)  NOT NULL,
    `postcode_out`    varchar(8)   NOT NULL,
    `longitude`       float(10, 6) NOT NULL,
    `latitude`        float(10, 6) NOT NULL,
    PRIMARY KEY (`postcode`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci;

# Table that we can load CodePoint Open (OS) data into.
DROP TABLE IF EXISTS `postcodes`;
CREATE TABLE `postcodes`
(
    `postcode`            varchar(8)  NOT NULL,
    `postcode_lookup`     varchar(15) NOT NULL,
    `east`                int(11)     NOT NULL,
    `north`               int(11)     NOT NULL,
    `admin_county_code`   VARCHAR(20) DEFAULT NULL,
    `admin_district_code` VARCHAR(20) DEFAULT NULL,
    `admin_ward_code`     VARCHAR(20) DEFAULT NULL,
    PRIMARY KEY (`postcode`)
) ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci;

# Table taht we load postcode_outs and their towns into from Wikipedia
DROP TABLE IF EXISTS `postcode_towns`;
CREATE TABLE `postcode_towns`
(
    `area`     varchar(8)  NOT NULL,
    `district` varchar(20) NOT NULL,
    `town`     varchar(60),
    `county`   varchar(80),
    PRIMARY KEY (`area`, `district`)
) ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci;

CREATE INDEX `district_name` ON postcode_towns (district) USING BTREE;
CREATE INDEX `town_name` ON postcode_towns (town) USING BTREE;
CREATE INDEX `district_town` ON postcode_towns (district, town) USING BTREE;
CREATE INDEX `postcode_out` ON postcode_zone (postcode_out) USING BTREE;
CREATE INDEX `long_lat` ON postcode_zone (longitude, latitude) USING BTREE;
CREATE INDEX `postcode_lookup` ON postcode_zone (postcode_lookup) USING BTREE;
CREATE INDEX `postcode_lookup` ON postcodes (postcode_lookup) USING BTREE;
